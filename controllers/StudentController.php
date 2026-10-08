<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/TuitionFee.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Refund.php';
require_once __DIR__ . '/../config/mailer.php';

class StudentController
{
    public static function payTuition(): void
    {
        Auth::requireRole('student');
        verify_csrf();

        $userId = Auth::userId();
        $student = Student::findByUserId($userId);
        if (!$student) redirect(APP_URL . '/public/student/');

        $feeId     = (int) ($_POST['fee_id'] ?? 0);
        $rawAmount = trim((string) ($_POST['amount'] ?? ''));
        $rawMethod = trim($_POST['payment_method'] ?? 'online');
        $allowedMethods = ['online', 'gcash', 'maya', 'bank_transfer', 'card'];
        $method = in_array($rawMethod, $allowedMethods) ? $rawMethod : 'online';

        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            Auth::setFlash('error', 'Please enter a valid positive payment amount.');
            redirect(APP_URL . '/public/student/');
        }

        $amount = round((float)$rawAmount, 2);

        if ($feeId <= 0) {
            // Auto fallback to student's latest active tuition fee if fee_id missing
            $latestFee = TuitionFee::findLatestByStudentId($student['id']);
            $feeId = $latestFee ? (int)$latestFee['id'] : 0;
        }

        $fee = $feeId > 0 ? TuitionFee::findById($feeId) : null;
        if (!$fee || (int)$fee['student_id'] !== (int)$student['id']) {
            Auth::setFlash('error', 'Invalid or unassigned tuition fee record.');
            redirect(APP_URL . '/public/student/');
        }

        $remaining = round(max(0, (float)$fee['total_amount'] - (float)$fee['amount_paid']), 2);
        if ($remaining <= 0) {
            Auth::setFlash('error', 'Your tuition assessment is already fully settled. No payment required.');
            redirect(APP_URL . '/public/student/');
        }

        if ($amount > $remaining) {
            Auth::setFlash('error', 'Payment amount (' . peso($amount) . ') cannot exceed your remaining balance of ' . peso($remaining));
            redirect(APP_URL . '/public/student/');
        }

        $orNumber = generateORNumber();

        // 1. Record payment transaction
        Payment::create($feeId, $student['id'], $orNumber, $amount, $method, 'Payment via Student Portal');

        // 2. Deduct from total tuition balance
        TuitionFee::recordPayment($feeId, $amount);

        // 3. Re-fetch updated fee balance
        $updatedFee = TuitionFee::findById($feeId);
        $newRemaining = round(max(0, (float)$updatedFee['total_amount'] - (float)$updatedFee['amount_paid']), 2);

        // 4. Send official receipt email
        $receiptHtml = Mailer::paymentReceiptHtml($student, $fee, $orNumber, $amount, $method, $newRemaining);

        Mailer::send($student['email'], "{$student['first_name']} {$student['last_name']}", "Payment Receipt: {$orNumber}", $receiptHtml, 'payment_confirmation', $feeId);

        if (!empty($student['parent_email'])) {
            Mailer::send($student['parent_email'], $student['parent_name'] ?? 'Parent', "Payment Confirmation: {$orNumber}", $receiptHtml, 'payment_confirmation', $feeId);
        }

        Auth::setFlash('payment_success', [
            'or_number' => $orNumber,
            'amount' => $amount,
            'method' => strtoupper($method),
            'fee_desc' => $fee['description'],
            'student_name' => "{$student['first_name']} {$student['last_name']}",
            'remaining' => $newRemaining,
            'date' => date('M d, Y h:i A')
        ]);

        redirect(APP_URL . '/public/student/');
    }

    /**
     * Submit Student Refund Request with required valid justification reason
     */
    public static function requestRefund(): void
    {
        Auth::requireRole('student');
        verify_csrf();

        $userId = Auth::userId();
        $student = Student::findByUserId($userId);
        if (!$student) {
            redirect(APP_URL . '/public/student/');
        }

        $feeId     = (int) ($_POST['fee_id'] ?? 0);
        $paymentId = !empty($_POST['payment_id']) ? (int) $_POST['payment_id'] : null;
        $rawAmount = trim((string) ($_POST['amount'] ?? ''));
        $reason    = trim((string) ($_POST['reason'] ?? ''));

        // Check if student has provided a valid, substantive reason
        if (empty($reason) || mb_strlen($reason) < 10) {
            Auth::setFlash('error', 'A valid and detailed reason (at least 10 characters) is required to submit a refund request for accounting review.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            Auth::setFlash('error', 'Please enter a valid positive refund amount.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        $amount = round((float)$rawAmount, 2);

        // Require user to explicitly select an assessment to refund
        if ($feeId <= 0) {
            Auth::setFlash('error', 'Please select which tuition assessment you are requesting a refund for.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        $fee = TuitionFee::findById($feeId);
        if (!$fee || (int)$fee['student_id'] !== (int)$student['id']) {
            Auth::setFlash('error', 'Invalid tuition assessment record selected.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        $paidAmount = (float) $fee['amount_paid'];
        if ($paidAmount <= 0) {
            Auth::setFlash('error', 'You have not made any payments for this tuition assessment yet. There are no refundable funds.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        // Calculate available refundable amount (total paid minus any pending refund requests)
        $pendingAmount = Refund::getPendingAmountForFee($feeId);
        $maxRefundable = round(max(0.0, $paidAmount - $pendingAmount), 2);

        if ($maxRefundable <= 0) {
            Auth::setFlash('error', 'All eligible paid funds for this assessment currently have pending refund requests under accounting review.');
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        if ($amount > $maxRefundable) {
            Auth::setFlash('error', 'Requested refund amount (' . peso($amount) . ') exceeds your available refundable balance of ' . peso($maxRefundable));
            redirect(APP_URL . '/public/student/?view=refunds');
        }

        // If specific payment was selected, ensure it belongs to this fee and student
        if ($paymentId !== null && $paymentId > 0) {
            $db = Database::getInstance();
            $pCheck = $db->prepare("SELECT * FROM payments WHERE id = ? AND student_id = ? AND tuition_fee_id = ?");
            $pCheck->execute([$paymentId, $student['id'], $feeId]);
            $paymentRow = $pCheck->fetch();
            if (!$paymentRow) {
                Auth::setFlash('error', 'The selected payment receipt does not match your assessment records.');
                redirect(APP_URL . '/public/student/?view=refunds');
            }
        }

        // Create the refund request record (status: pending)
        $refundId = Refund::create((int)$student['id'], $feeId, $paymentId, $amount, $reason);

        // Notify Accounting Office of the new refund request
        $accountingUser = User::findFirstByRole('accounting');
        $accountingEmail = $accountingUser['email'] ?? 'accounting@paytrack.edu.ph';
        $accountingName  = $accountingUser['name'] ?? 'Accounting Office';
        $studentFullName = "{$student['first_name']} {$student['last_name']}";

        $noticeHtml = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='background: #0f172a; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 20px;'>New Student Refund Request</h2>
                    <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>Action Required: Accounting Assessment &amp; Decision</p>
                </div>
                <p>Attention: <strong>{$accountingName}</strong>,</p>
                <p>Student <strong>{$studentFullName}</strong> ({$student['student_id']}) has submitted a tuition refund request awaiting your review:</p>
                <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                    <p style='margin: 4px 0;'><strong>Student Name:</strong> {$studentFullName} ({$student['student_id']})</p>
                    <p style='margin: 4px 0;'><strong>Assessment:</strong> {$fee['description']}</p>
                    <p style='margin: 4px 0;'><strong>Refund Amount Requested:</strong> <span style='font-size: 16px; font-weight: bold; color: #0b3d2e;'>" . peso($amount) . "</span></p>
                    <p style='margin: 4px 0;'><strong>Student Reason:</strong> <em>" . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "</em></p>
                    <p style='margin: 4px 0;'><strong>Current Amount Paid:</strong> " . peso($paidAmount) . "</p>
                </div>
                <p>Please log in to the Accounting Portal to assess the student's justification and either approve or reject the request.</p>
                <p style='margin-top: 20px;'>
                    <a href='" . APP_URL . "/public/accounting/?view=refunds' style='display: inline-block; background: #0f172a; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Open Refund Approvals &rarr;</a>
                </p>
            </div>";

        Mailer::send($accountingEmail, $accountingName, "PayTrack Notice: Refund Request from {$studentFullName} (" . peso($amount) . ")", $noticeHtml, 'other', $refundId);

        $createdRefund = Refund::findById($refundId);
        Auth::setFlash('refund_success', [
            'refund_number' => $createdRefund['refund_number'] ?? ('REF-' . date('Y') . '-' . $refundId),
            'amount' => (float)$amount,
            'fee_desc' => $fee['description'] ?? 'Tuition Assessment',
            'reason' => $reason,
            'date' => date('M d, Y h:i A')
        ]);
        Auth::setFlash('success', 'Your refund request for ' . peso($amount) . ' has been submitted. The Accounting Office will review your reason and evaluate the refund.');
        redirect(APP_URL . '/public/student/?view=refunds');
    }

    public static function updatePassword(): void
    {
        Auth::requireRole('student');
        Auth::setFlash('error', 'Password changes are disabled for students. Your login password is your Last Name, shared with your parents for tuition monitoring.');
        redirect(APP_URL . '/public/student/');
    }
}
