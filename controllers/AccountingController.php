<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/FeeCategory.php';
require_once __DIR__ . '/../models/TuitionFee.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Refund.php';
require_once __DIR__ . '/../config/mailer.php';

class AccountingController
{
    /** Allow the signed-in accounting user to securely change their password. */
    public static function changePassword(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $user = User::findById((int) Auth::userId());
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!$user || ($user['role'] ?? '') !== 'accounting' || !password_verify($currentPassword, $user['password_hash'] ?? '')) {
            Auth::setFlash('error', 'Your current password is incorrect.');
            redirect(APP_URL . '/public/accounting/?view=home');
        }
        if (strlen($newPassword) < 8) {
            Auth::setFlash('error', 'Your new password must be at least 8 characters long.');
            redirect(APP_URL . '/public/accounting/?view=home');
        }
        if ($newPassword !== $confirmPassword) {
            Auth::setFlash('error', 'The new password and confirmation do not match.');
            redirect(APP_URL . '/public/accounting/?view=home');
        }
        if (password_verify($newPassword, $user['password_hash'])) {
            Auth::setFlash('error', 'Choose a password different from your current password.');
            redirect(APP_URL . '/public/accounting/?view=home');
        }

        User::updatePassword((int) $user['id'], $newPassword);
        Auth::setFlash('success', 'Your password has been changed. Use the new password the next time you sign in.');
        redirect(APP_URL . '/public/accounting/?view=home');
    }

    /**
     * Assign / Update Student Tuition Fee Assessment & Class Details
     * Allows customizing individual fee prices and excluding specific fee categories
     */
    public static function assignTuitionFee(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $studentId = (int) ($_POST['student_id'] ?? 0);
        $student = Student::findById($studentId);
        if (!$student) {
            Auth::setFlash('error', 'Student record not found.');
            redirect(APP_URL . '/public/accounting/?view=students');
        }

        // Class & Academic Details
        $course      = trim($_POST['course'] ?? '');
        $sectionCode = trim($_POST['section_code'] ?? '');
        $gradeLevel  = trim($_POST['grade_level'] ?? ($student['grade_level'] ?? 'BSCS 11A1'));
        if (!empty($course) && !empty($sectionCode)) {
            $gradeLevel = "{$course} {$sectionCode}";
        }
        $schoolYear  = trim($_POST['school_year'] ?? (date('Y') . '-' . (date('Y') + 1)));
        $semester    = trim($_POST['semester'] ?? '1st Semester');
        $dueDate     = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
        $description = trim($_POST['description'] ?? "S.Y. {$schoolYear} - {$semester} Tuition");

        // Process customized fee category items
        // Form sends: fee_included[cat_id]=1 and fee_amount[cat_id]=123.45 and fee_name[cat_id]=...
        $includedCats = $_POST['fee_included'] ?? [];
        $amounts      = $_POST['fee_amount'] ?? [];
        $catNames     = $_POST['fee_name'] ?? [];

        $selectedItems = [];
        foreach ($includedCats as $catId => $val) {
            $catId = (int) $catId;
            $category = FeeCategory::findById($catId);
            if (!$category) {
                $category = FeeCategory::findByNameOrCode($catNames[$catId] ?? '');
            }
            if (!$category || empty($category['is_active'])) {
                continue;
            }
            $resolvedCatId = (int) $category['id'];
            
            // Allow accounting to customize any fee amount, or default to the category rate
            $rawAmt = isset($amounts[$catId]) && trim((string)$amounts[$catId]) !== ''
                ? trim((string) $amounts[$catId])
                : number_format((float) $category['default_amount'], 2, '.', '');

            if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $rawAmt)) {
                Auth::setFlash('error', 'Fee amounts must be non-negative numbers with no more than 2 decimal places.');
                redirect(APP_URL . '/public/accounting/?view=students');
            }
            $amt = (float) $rawAmt;
            $name = trim($catNames[$catId] ?? ($category['name'] ?? "Fee Aspect #{$resolvedCatId}"));

            $selectedItems[] = [
                'fee_category_id' => $resolvedCatId,
                'category_name'   => $name,
                'amount'          => $amt,
            ];
        }

        if (empty($selectedItems)) {
            Auth::setFlash('error', 'Please include at least one fee category in the assessment.');
            redirect(APP_URL . '/public/accounting/?view=students');
        }

        // Capture enrolled subjects JSON from form (submitted as hidden field)
        $enrolledSubjectsJson = trim($_POST['enrolled_subjects_json'] ?? '');
        if (empty($enrolledSubjectsJson) || !is_array(json_decode($enrolledSubjectsJson, true))) {
            $enrolledSubjectsJson = null;
        }

        // Update student class and academic details
        Student::updateClassDetails($studentId, $gradeLevel, $schoolYear);

        $feeId = (int) ($_POST['fee_id'] ?? 0);
        if ($feeId <= 0) {
            $existingFee = TuitionFee::findLatestByStudentId($studentId);
            if ($existingFee) {
                $feeId = (int)$existingFee['id'];
            }
        }

        if ($feeId > 0) {
            TuitionFee::updateCustomAssessment(
                $feeId,
                $studentId,
                $schoolYear,
                $semester,
                $description,
                !empty($dueDate) ? $dueDate : null,
                $selectedItems,
                $enrolledSubjectsJson
            );
            $createdFee = TuitionFee::findById($feeId);
            $actionWord = 'updated';
        } else {
            $feeId = TuitionFee::createCustomAssessment(
                $studentId,
                $schoolYear,
                $semester,
                $description,
                !empty($dueDate) ? $dueDate : null,
                $selectedItems,
                $enrolledSubjectsJson
            );
            $createdFee = TuitionFee::findById($feeId);
            $actionWord = 'posted';
        }

        // Always notify student and parents when accounting posts or updates tuition.
        if ($createdFee) {
            Mailer::sendTuitionAssessmentNotice($student, $createdFee, $selectedItems);
        }

        Auth::setFlash('success', "Tuition assessment of " . peso($createdFee['total_amount'] ?? 0) . " {$actionWord} for {$student['first_name']} {$student['last_name']}. Email notification sent to student and parents (when parent email is on file).");
        redirect(APP_URL . '/public/accounting/?view=students');
    }

    /**
     * Record Over-the-Counter / Manual Cash Payment by Accounting Staff
     */
    public static function recordManualPayment(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $feeId     = (int) ($_POST['fee_id'] ?? 0);
        $rawAmount = trim((string) ($_POST['amount'] ?? ''));
        $method    = trim($_POST['payment_method'] ?? 'cash');
        $notes     = trim($_POST['notes'] ?? 'Over-the-counter payment at Accounting Office');

        $allowed = ['cash', 'gcash', 'maya', 'bank_transfer', 'card', 'online', 'other'];
        if (!in_array($method, $allowed)) $method = 'cash';

        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            Auth::setFlash('error', 'Please enter a valid positive payment amount.');
            redirect(APP_URL . '/public/accounting/?view=transactions');
        }

        $amount = round((float)$rawAmount, 2);

        $fee = TuitionFee::findById($feeId);
        if (!$fee) {
            Auth::setFlash('error', 'Tuition assessment record not found.');
            redirect(APP_URL . '/public/accounting/?view=transactions');
        }

        $remaining = round(max(0, (float)$fee['total_amount'] - (float)$fee['amount_paid']), 2);
        if ($remaining <= 0) {
            Auth::setFlash('error', 'This tuition assessment is already fully settled and has no remaining balance.');
            redirect(APP_URL . '/public/accounting/?view=transactions');
        }

        if ($amount > $remaining) {
            Auth::setFlash('error', 'Payment amount (' . peso($amount) . ') cannot exceed remaining balance of ' . peso($remaining));
            redirect(APP_URL . '/public/accounting/?view=transactions');
        }

        $orNumber = generateORNumber();

        Payment::create($feeId, $fee['student_id'], $orNumber, $amount, $method, $notes);
        TuitionFee::recordPayment($feeId, $amount);

        // Send payment confirmation email
        $student = Student::findById($fee['student_id']);
        if ($student) {
            $updatedFee = TuitionFee::findById($feeId);
            $newRem = round(max(0, (float)$updatedFee['total_amount'] - (float)$updatedFee['amount_paid']), 2);

            $receiptHtml = Mailer::paymentReceiptHtml($student, $fee, $orNumber, $amount, $method, $newRem, true);
            Mailer::send($student['email'], "{$student['first_name']} {$student['last_name']}", "Payment Receipt: {$orNumber}", $receiptHtml, 'payment_confirmation', $feeId);
            if (!empty($student['parent_email'])) {
                Mailer::send($student['parent_email'], $student['parent_name'] ?? 'Parent', "Payment Confirmation: {$orNumber}", $receiptHtml, 'payment_confirmation', $feeId);
            }
        }

        Auth::setFlash('success', "Payment of " . peso($amount) . " recorded. Official Receipt {$orNumber} generated.");
        redirect(APP_URL . '/public/accounting/?view=transactions');
    }

    public static function saveFeeCategory(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $id = (int) ($_POST['category_id'] ?? 0);
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $defaultAmount = (float) ($_POST['default_amount'] ?? 0);
        $isVariable = !empty($_POST['is_variable']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 99);

        if (empty($name)) {
            Auth::setFlash('error', 'Category name is required.');
            redirect(APP_URL . '/public/accounting/?view=categories');
        }

        if ($id <= 0) {
            $db = Database::getInstance();
            $dup = $db->prepare("SELECT id FROM fee_categories WHERE LOWER(TRIM(name)) = LOWER(?) AND approval_status = 'pending' LIMIT 1");
            $dup->execute([$name]);
            if ($dup->fetch()) {
                Auth::setFlash('warning', "A pending approval request for '{$name}' has already been submitted to the Administrator.");
                redirect(APP_URL . '/public/accounting/?view=categories');
            }
        }

        if ($id > 0) {
            FeeCategory::update($id, [
                'name' => $name,
                'default_amount' => $defaultAmount,
                'is_variable' => $isVariable,
                'sort_order' => $sortOrder,
                'is_active' => 1
            ]);
            Auth::setFlash('success', 'Fee category updated.');
        } else {
            $catId = FeeCategory::requestCreate([
                'name' => $name,
                'default_amount' => $defaultAmount,
                'is_variable' => $isVariable,
                'sort_order' => $sortOrder
            ], (int) Auth::userId());

            // Notify Administrator via email and system audit log
            $adminUser = User::findByUsername('admin');
            $adminEmail = $adminUser['email'] ?? 'admin@ncst.edu.ph';
            $staff = User::findById((int) Auth::userId());
            $staffName = $staff ? ($staff['name'] ?? $staff['username']) : 'Accounting Staff';

            $subj = "Fee Category Approval Requested: {$name}";
            $body = "<div style='font-family:sans-serif;padding:20px;color:#1e293b;line-height:1.6;'>"
                . "<h2 style='color:#0b3d2e;margin-top:0;'>Fee Category Approval Request</h2>"
                . "<p>Accounting staff <strong>" . e($staffName) . "</strong> has requested to add a new fee category to the system:</p>"
                . "<div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0;'>"
                . "<p style='margin:4px 0;'><strong>Fee Category Name:</strong> " . e($name) . "</p>"
                . "<p style='margin:4px 0;'><strong>Default Rate:</strong> " . peso($defaultAmount) . "</p>"
                . "<p style='margin:4px 0;'><strong>Type:</strong> " . ($isVariable ? 'Variable Tuition (Subject Units)' : 'Fixed Institutional Fee') . "</p>"
                . "<p style='margin:4px 0;'><strong>Requested Date:</strong> " . date('M d, Y h:i A') . "</p>"
                . "</div>"
                . "<p>Please sign in to the Admin Portal to review and approve or reject this request.</p>"
                . "</div>";

            Mailer::send($adminEmail, 'PayTrack Administrator', $subj, $body, 'fee_category_request');

            Auth::setFlash('success', "Fee category '{$name}' requested successfully! It is now pending administrator approval before it can be used.");
        }

        redirect(APP_URL . '/public/accounting/?view=categories');
    }

    public static function deleteFeeCategory(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $id = (int) ($_POST['category_id'] ?? 0);
        if ($id <= 0) {
            $db = Database::getInstance();
            $dup = $db->prepare("SELECT id FROM fee_categories WHERE LOWER(TRIM(name)) = LOWER(?) AND approval_status = 'pending' LIMIT 1");
            $dup->execute([$name]);
            if ($dup->fetch()) {
                Auth::setFlash('warning', "A pending approval request for '{$name}' has already been submitted to the Administrator.");
                redirect(APP_URL . '/public/accounting/?view=categories');
            }
        }

        if ($id > 0) {
            try {
                FeeCategory::delete($id);
                Auth::setFlash('success', 'Fee category deleted successfully.');
            } catch (\RuntimeException $e) {
                Auth::setFlash('error', $e->getMessage());
            }
        }
        redirect(APP_URL . '/public/accounting/?view=categories');
    }

    public static function deleteFee(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $feeId = (int) ($_POST['fee_id'] ?? 0);
        if ($feeId > 0) {
            TuitionFee::delete($feeId);
            Auth::setFlash('success', 'Tuition assessment record deleted.');
        }
        redirect(APP_URL . '/public/accounting/?view=fees');
    }

    /**
     * Approve a student's refund request:
     * - Marks request approved with accounting remarks
     * - Deducts refunded amount from tuition assessment amount_paid (increasing remaining balance)
     * - Dispatches official approval notice to student & parent emails
     */
    public static function approveRefund(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $refundId = (int) ($_POST['refund_id'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        $refund = Refund::findById($refundId);
        if (!$refund || $refund['status'] !== 'pending') {
            Auth::setFlash('error', 'Refund request not found or already processed.');
            redirect(APP_URL . '/public/accounting/?view=refunds');
        }

        $success = Refund::approve($refundId, (int) Auth::userId(), $notes);
        if (!$success) {
            Auth::setFlash('error', 'Failed to approve refund request. Please check the assessment record.');
            redirect(APP_URL . '/public/accounting/?view=refunds');
        }

        // Fetch updated student and fee records for notification email
        $student = Student::findById((int) $refund['student_id']);
        $fee = TuitionFee::findById((int) $refund['tuition_fee_id']);
        $updatedRefund = Refund::findById($refundId);

        if ($student && $fee && $updatedRefund) {
            Mailer::sendRefundApprovalNotice($student, $updatedRefund, $fee, $notes);
        }

        Auth::setFlash('success', "Refund {$refund['refund_number']} for " . peso($refund['amount']) . " approved successfully! Student balance has been adjusted and confirmation emails dispatched to student and parent.");
        redirect(APP_URL . '/public/accounting/?view=refunds');
    }

    /**
     * Reject a student's refund request with explanation reason
     */
    public static function rejectRefund(): void
    {
        Auth::requireRole('accounting');
        verify_csrf();

        $refundId = (int) ($_POST['refund_id'] ?? 0);
        $rejectionReason = trim((string) ($_POST['rejection_reason'] ?? ''));

        if (empty($rejectionReason)) {
            Auth::setFlash('error', 'Please provide a valid explanation reason for rejecting the student refund request.');
            redirect(APP_URL . '/public/accounting/?view=refunds');
        }

        $refund = Refund::findById($refundId);
        if (!$refund || $refund['status'] !== 'pending') {
            Auth::setFlash('error', 'Refund request not found or already processed.');
            redirect(APP_URL . '/public/accounting/?view=refunds');
        }

        $success = Refund::reject($refundId, (int) Auth::userId(), $rejectionReason);
        if (!$success) {
            Auth::setFlash('error', 'Failed to reject refund request.');
            redirect(APP_URL . '/public/accounting/?view=refunds');
        }

        // Dispatch status update notice to student and parent
        $student = Student::findById((int) $refund['student_id']);
        $fee = TuitionFee::findById((int) $refund['tuition_fee_id']);
        $updatedRefund = Refund::findById($refundId);

        if ($student && $fee && $updatedRefund) {
            Mailer::sendRefundRejectionNotice($student, $updatedRefund, $fee, $rejectionReason);
        }

        Auth::setFlash('success', "Refund request {$refund['refund_number']} was rejected. Status update notice has been sent to the student and parent.");
        redirect(APP_URL . '/public/accounting/?view=refunds');
    }

    /**
     * Real-time polling API endpoint for Accounting Portal
     * Returns new payments, updated balances, and metrics in JSON format
     */
    public static function realtimeFeed(): void
    {
        Auth::requireRole('accounting');
        header('Content-Type: application/json; charset=utf-8');

        $db = Database::getInstance();
        $lastPaymentId = (int) ($_GET['last_payment_id'] ?? 0);

        // Fetch any new payments created after $lastPaymentId
        $newPaymentsStmt = $db->prepare("
            SELECT p.*, s.first_name, s.last_name, s.student_id as student_num, tf.description as fee_desc
            FROM payments p
            JOIN students s ON p.student_id = s.id
            JOIN tuition_fees tf ON p.tuition_fee_id = tf.id
            WHERE p.id > ?
            ORDER BY p.id ASC
        ");
        $newPaymentsStmt->execute([$lastPaymentId]);
        $newPayments = $newPaymentsStmt->fetchAll();

        // Calculate latest system-wide financial metrics
        $payments = Payment::all();
        $fees = TuitionFee::all();
        $totalRefunded = Refund::getTotalApprovedAmount();

        $totalRevenue = 0;
        foreach ($payments as $p) {
            $totalRevenue += (float) ($p['amount'] ?? 0);
        }
        $netRevenue = max(0.0, $totalRevenue - $totalRefunded);

        $totalAssessed = 0;
        foreach ($fees as $f) {
            $totalAssessed += (float) ($f['total_amount'] ?? 0);
        }

        $totalReceivables = max(0.0, $totalAssessed - $netRevenue);
        $collectionRate = ($totalAssessed > 0) ? min(100, round(($netRevenue / $totalAssessed) * 100, 1)) : 0;
        $pendingRefundsCount = Refund::countPending();

        // Fetch students with current balance
        $students = Student::all();
        $studentBalances = [];
        foreach ($students as $s) {
            $sFees = TuitionFee::getByStudentId($s['id']);
            $primaryFee = !empty($sFees) ? $sFees[0] : null;
            $tAmt = $primaryFee ? (float)$primaryFee['total_amount'] : 0.0;
            $pAmt = $primaryFee ? (float)$primaryFee['amount_paid'] : 0.0;
            $rAmt = max(0.0, $tAmt - $pAmt);

            $studentBalances[] = [
                'id' => (int)$s['id'],
                'student_id' => $s['student_id'],
                'name' => $s['first_name'] . ' ' . $s['last_name'],
                'has_assessment' => !empty($sFees),
                'total_amount' => $tAmt,
                'amount_paid' => $pAmt,
                'remaining_balance' => $rAmt,
                'formatted_balance' => peso($rAmt),
                'formatted_total' => peso($tAmt)
            ];
        }

        // Return each assessment separately so the live feed can update the
        // Accounting > Tuition Assessments table (students may have many fees).
        $assessmentBalances = [];
        foreach ($fees as $fee) {
            $feeTotal = (float) ($fee['total_amount'] ?? 0);
            $feePaid = (float) ($fee['amount_paid'] ?? 0);
            $feeRemaining = max(0.0, $feeTotal - $feePaid);
            $assessmentBalances[(int) $fee['id']] = [
                'total_amount' => $feeTotal,
                'amount_paid' => $feePaid,
                'remaining_balance' => $feeRemaining,
                'formatted_total' => peso($feeTotal),
                'formatted_paid' => peso($feePaid),
                'formatted_remaining' => peso($feeRemaining),
                'status' => $feeRemaining <= 0 ? 'paid' : ($feePaid > 0 ? 'partial' : 'unpaid')
            ];
        }

        // Fetch fee categories for real-time live sync
        $rawCategories = FeeCategory::all();
        $categoriesList = [];
        foreach ($rawCategories as $rcat) {
            $categoriesList[] = [
                'id' => (int) $rcat['id'],
                'name' => $rcat['name'],
                'code' => $rcat['code'],
                'default_amount' => (float) $rcat['default_amount'],
                'formatted_amount' => peso($rcat['default_amount']),
                'is_variable' => (int) ($rcat['is_variable'] ?? 0),
                'approval_status' => $rcat['approval_status'] ?? 'approved',
                'rejection_reason' => $rcat['rejection_reason'] ?? '',
                'sort_order' => (int) ($rcat['sort_order'] ?? 99),
                'requested_at' => $rcat['requested_at'] ?? '',
                'reviewed_at' => $rcat['reviewed_at'] ?? '',
            ];
        }
        $pendingApprovalsCount = FeeCategory::countPendingApprovals();

        // Check latest unread email logs / notifications
        $emailLogs = $db->query("SELECT id, recipient_email, subject, type, status, sent_at FROM email_logs ORDER BY sent_at DESC LIMIT 10")->fetchAll();

        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'new_payments_count' => count($newPayments),
            'new_payments' => $newPayments,
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_refunded' => $totalRefunded,
                'net_revenue' => $netRevenue,
                'total_receivables' => $totalReceivables,
                'collection_rate' => $collectionRate,
                'formatted_revenue' => peso($totalRevenue),
                'formatted_refunded' => peso($totalRefunded),
                'formatted_net_revenue' => peso($netRevenue),
                'formatted_receivables' => peso($totalReceivables),
                'formatted_collection_rate' => $collectionRate . '%',
                'pending_refunds_count' => $pendingRefundsCount,
            ],
            'students' => $studentBalances,
            'assessments' => $assessmentBalances,
            'recent_notifications' => $emailLogs,
            'categories' => $categoriesList,
            'pending_fee_count' => $pendingApprovalsCount,
            'categories_hash' => md5(json_encode($categoriesList))
        ]);
        exit;
    }
}
