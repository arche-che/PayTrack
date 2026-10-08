<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class Mailer
{
    private static ?object $sharedMailer = null;

    /** Shared branded payment notification for both student and cashier payments. */
    public static function paymentReceiptHtml(array $student, array $fee, string $orNumber, float $amount, string $method, float $remaining, bool $cashier = false): string
    {
        $name = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $studentId = htmlspecialchars((string) ($student['student_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars((string) ($fee['description'] ?? 'Tuition payment'), ENT_QUOTES, 'UTF-8');
        $methodLabel = htmlspecialchars(strtoupper(str_replace('_', ' ', $method)), ENT_QUOTES, 'UTF-8');
        $status = $remaining <= 0 ? 'Payment complete — assessment fully paid' : 'Payment recorded — balance remains';
        $statusColor = $remaining <= 0 ? '#15803d' : '#b45309';
        $channel = $cashier ? 'Recorded by the Accounting Office' : 'Recorded through the Student Portal';
        return "<div style='margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;'>
          <table role='presentation' style='width:100%;max-width:620px;margin:auto;border-collapse:collapse;background:#ffffff;border:1px solid #dbe3ec;border-radius:14px;overflow:hidden;'>
            <tr><td style='padding:24px 28px;background:#0b3d2e;color:#fff;'><div style='font-size:12px;letter-spacing:1px;font-weight:bold;opacity:.8'>PAYTRACK</div><div style='font-size:24px;font-weight:bold;margin-top:5px'>Payment received</div><div style='font-size:13px;margin-top:6px;opacity:.9'>Official electronic receipt and payment confirmation</div></td></tr>
            <tr><td style='padding:25px 28px;'><p style='margin:0 0 16px;font-size:15px'>Hello <strong>{$name}</strong>,</p><p style='margin:0 0 20px;font-size:14px;line-height:1.55'>Your payment has been successfully recorded. Keep this email as your payment confirmation.</p>
              <div style='padding:15px 16px;background:#f8fafc;border:1px solid #dbe3ec;border-radius:10px'>
                <div style='font-size:11px;color:#64748b;font-weight:bold;letter-spacing:.6px'>OFFICIAL RECEIPT NO.</div><div style='font-size:20px;font-family:monospace;font-weight:bold;color:#0b3d2e;margin:3px 0 14px'>{$orNumber}</div>
                <table role='presentation' style='width:100%;border-collapse:collapse;font-size:13px'><tr><td style='padding:6px 0;color:#64748b'>Student</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$name} ({$studentId})</td></tr><tr><td style='padding:6px 0;color:#64748b'>Assessment</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$description}</td></tr><tr><td style='padding:6px 0;color:#64748b'>Payment method</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$methodLabel}</td></tr><tr><td style='padding:12px 0 4px;color:#64748b;border-top:1px solid #dbe3ec'>Amount paid</td><td style='padding:12px 0 4px;text-align:right;font-size:19px;font-weight:bold;color:#15803d'>" . peso($amount) . "</td></tr><tr><td style='padding:6px 0;color:#64748b'>Remaining balance</td><td style='padding:6px 0;text-align:right;font-weight:bold'>" . peso($remaining) . "</td></tr></table>
              </div><div style='margin-top:16px;padding:10px 12px;background:#f8fafc;border-radius:8px;color:{$statusColor};font-size:13px;font-weight:bold'>{$status}</div><p style='margin:20px 0 0;color:#64748b;font-size:12px'>{$channel} on " . date('M d, Y h:i A') . ".</p></td></tr>
          </table></div>";
    }

    private static function getMailerInstance(): ?object
    {
        if (self::$sharedMailer === null && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USER;
                $mail->Password   = MAIL_PASS;
                $mail->CharSet    = 'UTF-8';
                $mail->Timeout    = 7; // Fast 7-second timeout
                $mail->SMTPKeepAlive = true; // Connection pooling across batch emails

                if (MAIL_PORT == 465) {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Port = MAIL_PORT;

                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]
                ];
                $mail->setFrom(MAIL_FROM, MAIL_NAME);
                self::$sharedMailer = $mail;
            } catch (\Exception $e) {
                self::$sharedMailer = null;
            }
        }
        return self::$sharedMailer;
    }

    /**
     * Send an email via PHPMailer (SMTP) with automatic audit logging and connection pooling
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $type = 'other',
        ?int $relatedId = null
    ): bool {
        // If placeholder credentials are used, simulate sending gracefully for local dev
        if (
            empty(MAIL_USER) ||
            MAIL_USER === 'your_email@gmail.com' ||
            MAIL_PASS === 'your_app_password'
        ) {
            self::logEmail($toEmail, $subject, $type, $relatedId, 'sent', 'Simulated: To send live emails, configure real SMTP credentials in config/config.php');
            return true;
        }

        $mail = self::getMailerInstance();
        if ($mail) {
            try {
                $mail->clearAddresses();
                $mail->clearAttachments();
                $mail->clearCustomHeaders();
                $mail->addAddress($toEmail, $toName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

                $mail->send();
                self::logEmail($toEmail, $subject, $type, $relatedId, 'sent');
                return true;
            } catch (\Exception $e) {
                $errMsg = $mail->ErrorInfo ?? $e->getMessage();
                self::logEmail($toEmail, $subject, $type, $relatedId, 'failed', $errMsg);
                try {
                    $mail->smtpClose();
                } catch (\Exception $ignore) {}
                self::$sharedMailer = null;
                return false;
            }
        }

        // Fallback simulation
        self::logEmail($toEmail, $subject, $type, $relatedId, 'sent', 'PHPMailer package not found. Sent via fallback simulation.');
        return true;
    }

    private static function logEmail(
        string $email,
        string $subject,
        string $type,
        ?int $relatedId,
        string $status,
        ?string $err = null
    ): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO email_logs (recipient_email, subject, type, related_id, status, error_message, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$email, $subject, $type, $relatedId, $status, $err]);
        } catch (\Exception $e) {
            // Error logging failure should not break app flow
        }
    }

    public static function sendStudentRegistrationEmails(array $student, string $rawPassword): void
    {
        $loginUrl = APP_URL . '/public/';
        $studentName = "{$student['first_name']} {$student['last_name']}";

        // 1. Student Email
        $studentHtml = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='background: #0b3d2e; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 20px;'>Welcome to PayTrack!</h2>
                    <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>Student Tuition Fee Management System</p>
                </div>
                <p>Hello <strong>{$studentName}</strong>,</p>
                <p>Your student account has been registered by the administration. You can now log in to check your tuition assessment, official receipts, and account updates.</p>
                <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                    <p style='margin: 4px 0;'><strong>Student ID (Username):</strong> <code style='font-size: 15px; color: #0b3d2e;'>{$student['student_id']}</code></p>
                    <p style='margin: 4px 0;'><strong>Default Password:</strong> <code style='font-size: 15px; color: #0b3d2e;'>{$rawPassword}</code> (Your Last Name)</p>
                    <p style='margin: 8px 0 0; font-size: 12px; color: #64748b;'><em>Note: Your default password is your Last Name, shared with your parents for tuition transparency.</em></p>
                </div>
                <p style='margin-top: 20px;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: #0b3d2e; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Login to PayTrack Portal &rarr;</a>
                </p>
            </div>
        ";
        self::send($student['email'], $studentName, "PayTrack — Student Portal Account Created ({$student['student_id']})", $studentHtml, 'account_created', $student['id'] ?? null);

        // 2. Parent Email
        if (!empty($student['parent_email'])) {
            $parentName = $student['parent_name'] ?? 'Parent / Guardian';
            $parentHtml = "
                <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                    <div style='background: #0b3d2e; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                        <h2 style='margin: 0; font-size: 20px;'>PayTrack — Parent Account Access</h2>
                        <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>Tuition Monitoring for {$studentName}</p>
                    </div>
                    <p>Dear <strong>{$parentName}</strong>,</p>
                    <p>A PayTrack account has been created for your student, <strong>{$studentName}</strong>. You may use the student's ID and shared password to log in and monitor balances, review official electronic receipts, and track payment schedules.</p>
                    <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                        <p style='margin: 4px 0;'><strong>Student ID:</strong> <code style='font-size: 15px; color: #0b3d2e;'>{$student['student_id']}</code></p>
                        <p style='margin: 4px 0;'><strong>Shared Password:</strong> <code style='font-size: 15px; color: #0b3d2e;'>{$rawPassword}</code> (Student's Last Name)</p>
                    </div>
                    <p style='margin-top: 20px;'>
                        <a href='{$loginUrl}' style='display: inline-block; background: #0b3d2e; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Open PayTrack Portal &rarr;</a>
                    </p>
                </div>
            ";
            self::send($student['parent_email'], $parentName, "PayTrack — Account Access for {$studentName}", $parentHtml, 'account_created', $student['id'] ?? null);
        }

        // 3. Accounting Staff Email (Alerting Accounting Office of newly registered student)
        $accountingUser = User::findFirstByRole('accounting');
        $accountingEmail = $accountingUser['email'] ?? 'accounting@paytrack.edu.ph';
        $accountingName  = $accountingUser['name'] ?? 'Accounting Office';

        $accountingHtml = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='background: #0f172a; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 20px;'>New Student Registration Notice</h2>
                    <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>Action Required: Class Details & Tuition Assessment</p>
                </div>
                <p>Attention: <strong>{$accountingName}</strong>,</p>
                <p>A new student has been registered into PayTrack by the administration and is awaiting class assignment and tuition assessment:</p>
                <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                    <p style='margin: 4px 0;'><strong>Student Name:</strong> {$studentName}</p>
                    <p style='margin: 4px 0;'><strong>Student ID:</strong> <code style='font-size: 14px; color: #0f172a;'>{$student['student_id']}</code></p>
                    <p style='margin: 4px 0;'><strong>Email:</strong> {$student['email']}</p>
                    <p style='margin: 4px 0;'><strong>Status:</strong> <span style='color: #b45309; font-weight: bold;'>Pending Tuition Assessment</span></p>
                </div>
                <p>Please log in to the Accounting Portal to assign the student's class schedule, review fee categories, and post the assessment.</p>
                <p style='margin-top: 20px;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: #0f172a; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Go to Accounting Portal &rarr;</a>
                </p>
            </div>
        ";
        self::send($accountingEmail, $accountingName, "PayTrack Notice: New Student Enrolled ({$student['student_id']}) — Assessment Required", $accountingHtml, 'new_student_accounting_alert', $student['id'] ?? null);
    }

    public static function sendAccountingCredentials(string $toEmail, string $toName, string $username, string $rawPassword): void
    {
        $loginUrl = APP_URL . '/public/';
        $html = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='background: #0f172a; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 20px;'>PayTrack — Accounting Staff Credentials</h2>
                    <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>Finance & Assessment Operations Access</p>
                </div>
                <p>Hello <strong>{$toName}</strong>,</p>
                <p>An Accounting Staff account has been created for you by the System Administrator. You can sign in using the unified login form to manage student tuition fees, assign assessments, and record payment transactions.</p>
                <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                    <p style='margin: 4px 0;'><strong>Portal Username:</strong> <code style='font-size: 15px; color: #0f172a;'>{$username}</code></p>
                    <p style='margin: 4px 0;'><strong>Password:</strong> <code style='font-size: 15px; color: #0f172a;'>{$rawPassword}</code></p>
                    <p style='margin: 4px 0;'><strong>Assigned Role:</strong> <strong>Accounting Staff</strong></p>
                </div>
                <p style='margin-top: 20px;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: #0f172a; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Login to Accounting Portal &rarr;</a>
                </p>
            </div>
        ";
        self::send($toEmail, $toName, "PayTrack — Accounting Staff Account Created", $html, 'accounting_account_created');
    }

    public static function sendTuitionAssessmentNotice(array $student, array $fee, array $items = []): void
    {
        $loginUrl = APP_URL . '/public/';
        $studentName = "{$student['first_name']} {$student['last_name']}";
        $itemsHtml = '';
        foreach ($items as $it) {
            $amt = peso($it['amount'] ?? 0);
            $catName = htmlspecialchars($it['category_name'] ?? 'Fee Item');
            $itemsHtml .= "<tr><td style='padding: 6px 10px; border-bottom: 1px solid #f1f5f9;'>{$catName}</td><td style='padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: bold;'>{$amt}</td></tr>";
        }
        $totalFormatted = peso($fee['total_amount'] ?? 0);

        $html = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='background: #0b3d2e; color: #ffffff; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 20px;'>Tuition Assessment Posted</h2>
                    <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px;'>PayTrack — {$fee['description']}</p>
                </div>
                <p>Hello <strong>{$studentName}</strong>,</p>
                <p>Your tuition fee assessment and class schedule details have been officially evaluated and posted by the Accounting Office.</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 16px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;'>
                    <thead>
                        <tr style='background: #e2e8f0; text-align: left;'>
                            <th style='padding: 8px 10px;'>Assessment Breakdown</th>
                            <th style='padding: 8px 10px; text-align: right;'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                        <tr style='background: #f1f5f9; font-size: 14px;'>
                            <td style='padding: 10px; font-weight: bold;'>Total Assessment Due</td>
                            <td style='padding: 10px; text-align: right; font-weight: bold; color: #0b3d2e;'>{$totalFormatted}</td>
                        </tr>
                    </tbody>
                </table>
                <p>Due Date: <strong>" . (!empty($fee['due_date']) ? date('M d, Y', strtotime($fee['due_date'])) : 'Per Academic Calendar') . "</strong></p>
                <p style='margin-top: 20px;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: #0b3d2e; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;'>View Breakdown & Pay Online &rarr;</a>
                </p>
            </div>
        ";

        // Send to Student
        self::send($student['email'], $studentName, "PayTrack — Tuition Assessment: {$fee['description']}", $html, 'tuition_assessed', $fee['id'] ?? null);

        // Send to Parent if email exists
        if (!empty($student['parent_email'])) {
            self::send($student['parent_email'], $student['parent_name'] ?? 'Parent', "PayTrack — Tuition Assessment for {$studentName}", $html, 'tuition_assessed', $fee['id'] ?? null);
        }
    }

    /**
     * Send Refund Approval notification email to student and parent
     */
    public static function sendRefundApprovalNotice(array $student, array $refund, array $fee, ?string $accountingNotes = null): void
    {
        $loginUrl = APP_URL . '/public/';
        $studentName = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $studentId = htmlspecialchars((string) ($student['student_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $refundNumber = htmlspecialchars((string) ($refund['refund_number'] ?? ''), ENT_QUOTES, 'UTF-8');
        $refundAmount = (float) ($refund['amount'] ?? 0);
        $reason = htmlspecialchars((string) ($refund['reason'] ?? ''), ENT_QUOTES, 'UTF-8');
        $feeDesc = htmlspecialchars((string) ($fee['description'] ?? 'Tuition Assessment'), ENT_QUOTES, 'UTF-8');
        $orInfo = !empty($refund['or_number']) ? htmlspecialchars((string) $refund['or_number'], ENT_QUOTES, 'UTF-8') : 'Direct Assessment Refund';

        $totalFee = (float) ($fee['total_amount'] ?? 0);
        $amountPaid = (float) ($fee['amount_paid'] ?? 0);
        $remainingBalance = max(0.0, $totalFee - $amountPaid);

        $notesBlock = '';
        if (!empty($accountingNotes)) {
            $notesEscaped = htmlspecialchars(trim($accountingNotes), ENT_QUOTES, 'UTF-8');
            $notesBlock = "
                <div style='margin-top: 14px; padding: 12px 14px; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 6px;'>
                    <div style='font-size: 11.5px; font-weight: bold; color: #065f46; letter-spacing: 0.5px;'>ACCOUNTING OFFICE REMARKS</div>
                    <div style='font-size: 13px; color: #047857; margin-top: 3px;'>{$notesEscaped}</div>
                </div>";
        }

        $html = "
            <div style='margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;'>
                <table role='presentation' style='width:100%;max-width:620px;margin:auto;border-collapse:collapse;background:#ffffff;border:1px solid #dbe3ec;border-radius:14px;overflow:hidden;'>
                    <tr>
                        <td style='padding:24px 28px;background:#0b3d2e;color:#fff;'>
                            <div style='font-size:12px;letter-spacing:1px;font-weight:bold;opacity:.8'>PAYTRACK TUITION MANAGEMENT</div>
                            <div style='font-size:24px;font-weight:bold;margin-top:5px'>Refund Request Approved</div>
                            <div style='font-size:13px;margin-top:6px;opacity:.9'>Official Notice of Tuition Adjustment &amp; Refund Approval</div>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding:25px 28px;'>
                            <p style='margin:0 0 16px;font-size:15px'>Hello <strong>{$studentName}</strong>,</p>
                            <p style='margin:0 0 20px;font-size:14px;line-height:1.55'>
                                Your request for tuition refund has been <strong>reviewed and officially approved</strong> by the Accounting Office.
                                The refunded amount has been processed and your student tuition ledger and balance have been adjusted accordingly.
                            </p>

                            <div style='padding:16px 18px;background:#f8fafc;border:1px solid #dbe3ec;border-radius:10px;'>
                                <div style='display:flex;justify-content:space-between;align-items:center;'>
                                    <div>
                                        <div style='font-size:11px;color:#64748b;font-weight:bold;letter-spacing:.6px'>REFUND REFERENCE NO.</div>
                                        <div style='font-size:18px;font-family:monospace;font-weight:bold;color:#0b3d2e;margin:2px 0 10px'>{$refundNumber}</div>
                                    </div>
                                    <div style='display:inline-block;padding:4px 10px;background:#dcfce7;color:#166534;font-size:12px;font-weight:bold;border-radius:6px;'>
                                        APPROVED
                                    </div>
                                </div>

                                <table role='presentation' style='width:100%;border-collapse:collapse;font-size:13px;'>
                                    <tr><td style='padding:6px 0;color:#64748b'>Student ID</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$studentId}</td></tr>
                                    <tr><td style='padding:6px 0;color:#64748b'>Assessment Term</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$feeDesc}</td></tr>
                                    <tr><td style='padding:6px 0;color:#64748b'>Associated Receipt / Transaction</td><td style='padding:6px 0;text-align:right;font-weight:bold'>{$orInfo}</td></tr>
                                    <tr><td style='padding:6px 0;color:#64748b'>Student Stated Reason</td><td style='padding:6px 0;text-align:right;font-style:italic;'>{$reason}</td></tr>
                                    <tr>
                                        <td style='padding:12px 0 4px;color:#0f172a;font-weight:bold;border-top:1px solid #dbe3ec'>Amount Refunded</td>
                                        <td style='padding:12px 0 4px;text-align:right;font-size:20px;font-weight:bold;color:#15803d'>" . peso($refundAmount) . "</td>
                                    </tr>
                                </table>
                            </div>

                            {$notesBlock}

                            <div style='margin-top:18px;padding:14px 16px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;'>
                                <div style='font-size:12px;font-weight:bold;color:#334155;margin-bottom:8px;'>UPDATED TUITION LEDGER SUMMARY:</div>
                                <table role='presentation' style='width:100%;border-collapse:collapse;font-size:12.5px;'>
                                    <tr><td style='padding:4px 0;color:#64748b;'>Total Assessed:</td><td style='padding:4px 0;text-align:right;font-weight:600;'>" . peso($totalFee) . "</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b;'>Adjusted Amount Paid:</td><td style='padding:4px 0;text-align:right;font-weight:600;color:#059669;'>" . peso($amountPaid) . "</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b;font-weight:bold;border-top:1px solid #e2e8f0;'>Current Remaining Balance:</td><td style='padding:4px 0;text-align:right;font-weight:bold;color:#b45309;border-top:1px solid #e2e8f0;'>" . peso($remainingBalance) . "</td></tr>
                                </table>
                            </div>

                            <p style='margin:20px 0 0;color:#64748b;font-size:12px;line-height:1.45;'>
                                Action finalized by the Accounting Office on " . date('M d, Y h:i A') . ". You can sign in to the Student / Parent Portal to view your complete transaction history.
                            </p>

                            <p style='margin-top: 22px; text-align: center;'>
                                <a href='{$loginUrl}' style='display: inline-block; background: #0b3d2e; color: #ffffff; padding: 11px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13.5px;'>
                                    View in Student Portal &rarr;
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";

        // Send to Student
        self::send($student['email'], $studentName, "PayTrack — Refund Approved: {$refundNumber} (" . peso($refundAmount) . ")", $html, 'refund_approved', (int)($refund['id'] ?? null));

        // Send to Parent if parent email exists
        if (!empty($student['parent_email'])) {
            $parentName = htmlspecialchars($student['parent_name'] ?? 'Parent / Guardian', ENT_QUOTES, 'UTF-8');
            self::send($student['parent_email'], $parentName, "PayTrack Notice — Refund Approved for {$studentName} ({$refundNumber})", $html, 'refund_approved', (int)($refund['id'] ?? null));
        }
    }

    /**
     * Send Refund Rejection notification email to student and parent
     */
    public static function sendRefundRejectionNotice(array $student, array $refund, array $fee, string $rejectionReason): void
    {
        $loginUrl = APP_URL . '/public/';
        $studentName = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $studentId = htmlspecialchars((string) ($student['student_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $refundNumber = htmlspecialchars((string) ($refund['refund_number'] ?? ''), ENT_QUOTES, 'UTF-8');
        $refundAmount = (float) ($refund['amount'] ?? 0);
        $reason = htmlspecialchars((string) ($refund['reason'] ?? ''), ENT_QUOTES, 'UTF-8');
        $feeDesc = htmlspecialchars((string) ($fee['description'] ?? 'Tuition Assessment'), ENT_QUOTES, 'UTF-8');
        $rejReasonEscaped = htmlspecialchars(trim($rejectionReason), ENT_QUOTES, 'UTF-8');

        $html = "
            <div style='margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;'>
                <table role='presentation' style='width:100%;max-width:620px;margin:auto;border-collapse:collapse;background:#ffffff;border:1px solid #dbe3ec;border-radius:14px;overflow:hidden;'>
                    <tr>
                        <td style='padding:24px 28px;background:#991b1b;color:#fff;'>
                            <div style='font-size:12px;letter-spacing:1px;font-weight:bold;opacity:.8'>PAYTRACK TUITION MANAGEMENT</div>
                            <div style='font-size:24px;font-weight:bold;margin-top:5px'>Refund Request Status Update</div>
                            <div style='font-size:13px;margin-top:6px;opacity:.9'>Notice Regarding Refund Request {$refundNumber}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding:25px 28px;'>
                            <p style='margin:0 0 16px;font-size:15px'>Hello <strong>{$studentName}</strong>,</p>
                            <p style='margin:0 0 20px;font-size:14px;line-height:1.55'>
                                Your refund request has been evaluated by the Accounting Office. After careful assessment of the request and payment records, the refund request has been <strong>rejected</strong>.
                            </p>

                            <div style='padding:16px 18px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:16px;'>
                                <div style='font-size:11.5px;font-weight:bold;color:#991b1b;letter-spacing:0.5px;'>REASON FOR REJECTION:</div>
                                <div style='font-size:14px;color:#7f1d1d;margin-top:5px;line-height:1.5;'>{$rejReasonEscaped}</div>
                            </div>

                            <div style='padding:14px 16px;background:#f8fafc;border:1px solid #dbe3ec;border-radius:10px;'>
                                <table role='presentation' style='width:100%;border-collapse:collapse;font-size:13px;'>
                                    <tr><td style='padding:4px 0;color:#64748b'>Refund Reference</td><td style='padding:4px 0;text-align:right;font-weight:bold'>{$refundNumber}</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b'>Student ID</td><td style='padding:4px 0;text-align:right;font-weight:bold'>{$studentId}</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b'>Assessment</td><td style='padding:4px 0;text-align:right;font-weight:bold'>{$feeDesc}</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b'>Requested Amount</td><td style='padding:4px 0;text-align:right;font-weight:bold'>" . peso($refundAmount) . "</td></tr>
                                    <tr><td style='padding:4px 0;color:#64748b'>Original Student Reason</td><td style='padding:4px 0;text-align:right;font-style:italic;'>{$reason}</td></tr>
                                </table>
                            </div>

                            <p style='margin:20px 0 0;color:#64748b;font-size:12px;line-height:1.45;'>
                                If you believe this decision was made in error or have additional supporting documents, please contact or visit the Accounting Office.
                            </p>

                            <p style='margin-top: 22px; text-align: center;'>
                                <a href='{$loginUrl}' style='display: inline-block; background: #0f172a; color: #ffffff; padding: 11px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13.5px;'>
                                    Open Student Portal &rarr;
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";

        // Send to Student
        self::send($student['email'], $studentName, "PayTrack — Refund Request Update: {$refundNumber}", $html, 'refund_rejected', (int)($refund['id'] ?? null));

        // Send to Parent if parent email exists
        if (!empty($student['parent_email'])) {
            $parentName = htmlspecialchars($student['parent_name'] ?? 'Parent / Guardian', ENT_QUOTES, 'UTF-8');
            self::send($student['parent_email'], $parentName, "PayTrack Notice — Refund Request Update for {$studentName} ({$refundNumber})", $html, 'refund_rejected', (int)($refund['id'] ?? null));
        }
    }
}

