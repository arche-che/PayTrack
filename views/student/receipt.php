<?php
/**
 * National College of Science and Technology (NCST)
 * Certificate of Registration (COR) / Assessment of Fees & Official Electronic Receipt
 * Matches NCST Official Voucher Layout with Specified Subjects Table & Itemized Fee Breakdown.
 */

$studentName   = $student ? trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']) : 'DELA CRUZ, JUAN MIGUEL';
$studentNum    = $student ? ($student['student_id'] ?? '2023-53512') : '2023-53512';
$studentEmail  = $student['email'] ?? '—';
$parentName    = !empty($student['parent_name']) ? $student['parent_name'] : '—';
$parentContact = !empty($student['contact_number']) ? $student['contact_number'] : (!empty($student['contact']) ? $student['contact'] : '—');
$gradeLevelRaw = $student['grade_level'] ?? '41A2';
$courseHint    = $student['course'] ?? null;

$p = $selectedPayment ?? (!empty($payments) ? $payments[0] : null);
$orNumber = $p['or_number'] ?? generateORNumber();
$amountPaid = (float) ($p['amount'] ?? 0);
$method   = strtoupper($p['payment_method'] ?? 'ONLINE');
$paidAt   = !empty($p['paid_at']) ? date('M d, Y h:i A', strtotime($p['paid_at'])) : date('M d, Y h:i A');
$feeDesc  = $p['fee_desc'] ?? 'Tuition Fee Assessment Installment';

// Parse Year, Semester & retrieve specified subjects and fee schedule
$assessmentData = Curriculum::getAssessmentData($gradeLevelRaw, !empty($totalFees) ? $totalFees : null, $courseHint);
$parsed         = $assessmentData['parsed'];
$subjects       = $assessmentData['subjects'];
$paymentSubjects = json_decode((string)($p['enrolled_subjects_snapshot'] ?? ''), true);
$currentSubjects = json_decode((string)($p['current_enrolled_subjects'] ?? ''), true);
if (is_array($paymentSubjects)) {
    $subjects = $paymentSubjects;
}
$currentSubjectCodes = is_array($currentSubjects)
    ? array_map(static fn($subject) => strtoupper(trim((string)($subject['code'] ?? ''))), $currentSubjects)
    : array_map(static fn($subject) => strtoupper(trim((string)($subject['code'] ?? ''))), $subjects);
$totalUnits     = $assessmentData['total_units'];
$breakdown      = $assessmentData['breakdown'];
$cashTotal      = $assessmentData['cash_total'];
$installmentTot = $assessmentData['installment_total'];
$installmentChg = $assessmentData['installment_charge'];

$schoolYear = $student['school_year'] ?? '2026-2027';
$courseCode = $parsed['course'] ?? 'BSIT';
$courseName = $parsed['course_name'] ?? 'BS Information Technology';
$yearText   = $parsed['year_text'] ?? '4th Year';
$semText    = $parsed['sem_text'] ?? '1st Semester';
$sectionCode= $parsed['section_code'] ?? '41A2';

$remainingBalance = max(0, $cashTotal - $amountPaid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCST Certificate of Registration &amp; Receipt - <?= e($orNumber) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #111827;
            padding: 24px 12px;
            display: flex;
            justify-content: center;
        }
        .cor-page {
            width: 100%;
            max-width: 960px;
            background: #ffffff;
            border: 2px solid #000000;
            padding: 22px 28px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            font-size: 11px;
            line-height: 1.35;
        }

        /* Top Header */
        .cor-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0b3d2e;
            margin-bottom: 2px;
        }
        .header-sub {
            font-size: 11px;
            color: #374151;
            margin-bottom: 6px;
        }
        .cor-doc-badge {
            display: inline-block;
            font-size: 13px;
            font-weight: 800;
            color: #000000;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: 1.5px solid #000000;
            padding: 3px 18px;
            background: #f8fafc;
        }
        .cor-ref-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 6px;
            font-weight: 600;
        }

        /* Student Info Box */
        .student-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            border: 1.5px solid #000000;
            margin-bottom: 14px;
            background: #ffffff;
        }
        .info-cell {
            padding: 4px 8px;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        .info-cell:nth-child(3n) {
            border-right: none;
        }
        .info-cell.no-bottom {
            border-bottom: none;
        }
        .info-lbl {
            font-size: 9.5px;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
        }
        .info-val {
            font-size: 11.5px;
            font-weight: 800;
            color: #000000;
        }

        /* Specified Subjects Table */
        .section-title {
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            background: #0b3d2e;
            color: #ffffff;
            padding: 4px 8px;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000000;
            margin-bottom: 14px;
            font-size: 10.5px;
        }
        .subjects-table th {
            background: #e2e8f0;
            color: #000000;
            font-weight: 800;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #000000;
            text-align: center;
            font-size: 9.5px;
        }
        .subjects-table td {
            padding: 4px 6px;
            border: 1px solid #000000;
            color: #111827;
        }
        .subjects-table td.center {
            text-align: center;
        }
        .subjects-table td.right {
            text-align: right;
        }
        .subjects-table tr.total-row td {
            font-weight: 800;
            background: #f1f5f9;
        }

        /* Two Columns: Fee Assessment & Payment Details */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        /* Fee Assessment Box */
        .fees-box {
            border: 1.5px solid #000000;
            padding: 0;
        }
        .fees-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .fees-table th {
            background: #e2e8f0;
            border-bottom: 1px solid #000000;
            padding: 4px 8px;
            text-align: left;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
        }
        .fees-table td {
            padding: 3.5px 8px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .fees-table tr.highlight-total td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            background: #f8fafc;
            font-weight: 800;
            font-size: 11px;
        }

        /* Electronic Receipt / Payment Record Box */
        .receipt-box {
            border: 1.5px solid #000000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .receipt-inner {
            padding: 10px 12px;
        }
        .rcpt-header-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #000000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .rcpt-or-badge {
            font-family: monospace;
            font-size: 13px;
            font-weight: 800;
            color: #0b3d2e;
        }
        .rcpt-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11px;
        }
        .rcpt-row strong {
            color: #000000;
        }
        .paid-badge-box {
            background: #ecfdf5;
            border: 1.5px solid #059669;
            padding: 8px;
            text-align: center;
            margin-top: 8px;
            border-radius: 4px;
        }
        .paid-title {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #065f46;
        }
        .paid-amt {
            font-size: 20px;
            font-weight: 900;
            color: #047857;
            margin: 2px 0;
        }

        /* Signatures Footer */
        .signatures-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1.5px solid #000000;
            font-size: 10px;
        }
        .sig-col {
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #000000;
            height: 28px;
            margin-bottom: 4px;
        }
        .sig-title {
            font-weight: 800;
            text-transform: uppercase;
        }

        /* Action Buttons */
        .actions-bar {
            margin-top: 16px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-print {
            background: #0b3d2e;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #14532d;
        }
        .btn-back {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Strict Print Rules */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .cor-page {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 12px 16px !important;
                margin: 0 auto !important;
            }
            .actions-bar, .no-print {
                display: none !important;
            }
            @page {
                size: letter portrait;
                margin: 8mm 6mm;
            }
        }
    </style>
</head>
<body>

<div class="cor-page">
    <!-- Header -->
    <div class="cor-header">
        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #4b5563;">Republic of the Philippines</div>
        <div class="header-title">National College of Science and Technology</div>
        <div class="header-sub">Emilio Aguinaldo Highway, Dasmariñas City, Cavite 4114 &bull; Tel. No. (046) 416-0166</div>
        <div class="cor-doc-badge">CERTIFICATE OF REGISTRATION / ASSESSMENT OF FEES</div>
        <div class="cor-ref-line">
            <span>REFERENCE / OR NO: <strong style="font-family: monospace; font-size: 12px; color: #0b3d2e;"><?= e($orNumber) ?></strong></span>
            <span>PRINT DATE: <strong><?= date('m/d/Y h:i A') ?></strong></span>
        </div>
    </div>

    <!-- Student Information Box -->
    <div class="student-info-grid">
        <div class="info-cell">
            <div class="info-lbl">Student Name</div>
            <div class="info-val"><?= e($studentName) ?></div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">Student No</div>
            <div class="info-val" style="font-family: monospace;"><?= e($studentNum) ?></div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">Course / Program</div>
            <div class="info-val"><?= e($courseCode) ?> - <?= e($courseName) ?></div>
        </div>

        <div class="info-cell">
            <div class="info-lbl">Year Level</div>
            <div class="info-val"><?= e($yearText) ?></div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">Section Code</div>
            <div class="info-val"><?= e($sectionCode) ?> (<?= e($parsed['full_section']) ?>)</div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">School Year &amp; Semester</div>
            <div class="info-val">S.Y. <?= e($schoolYear) ?> &bull; <?= e($semText) ?></div>
        </div>

        <div class="info-cell no-bottom">
            <div class="info-lbl">Student Email</div>
            <div class="info-val"><?= e($studentEmail) ?></div>
        </div>
        <div class="info-cell no-bottom">
            <div class="info-lbl">Parent / Guardian</div>
            <div class="info-val"><?= e($parentName) ?></div>
        </div>
        <div class="info-cell no-bottom">
            <div class="info-lbl">Parent Contact No</div>
            <div class="info-val"><?= e($parentContact) ?></div>
        </div>
    </div>

    <!-- Specified Subjects Schedule Table -->
    <div class="section-title">SCHEDULE OF SPECIFIED ENROLLED SUBJECTS</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 10%;">Subject Code</th>
                <th style="width: 32%;">Descriptive Title</th>
                <th style="width: 6%;">Units</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 9%;">Days</th>
                <th style="width: 8%;">Start</th>
                <th style="width: 8%;">End</th>
                <th style="width: 7%;">Section</th>
                <th style="width: 12%;">Room</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $s): ?>
                <?php $subjectRemoved = !in_array(strtoupper(trim((string)($s['code'] ?? ''))), $currentSubjectCodes, true); ?>
                <tr>
                    <td class="center" style="font-weight: 800; font-family: monospace;<?= $subjectRemoved ? ' color:#dc2626;' : '' ?>"><?= e($s['code']) ?></td>
                    <td style="<?= $subjectRemoved ? 'color:#dc2626;' : '' ?>"><strong><?= e($s['description']) ?></strong></td>
                    <td class="center" style="font-weight: 800;"><?= e($s['units']) ?></td>
                    <td class="center"><?= e($s['type']) ?></td>
                    <td class="center"><?= e($s['days']) ?></td>
                    <td class="center"><?= e($s['start_time']) ?></td>
                    <td class="center"><?= e($s['end_time']) ?></td>
                    <td class="center" style="font-weight: 700;"><?= e($s['section']) ?></td>
                    <td class="center"><?= e($s['room']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding-right: 12px;">TOTAL NUMBER OF ACADEMIC UNITS:</td>
                <td class="center" style="font-size: 12px; color: #0b3d2e; font-weight: 900;"><?= e($totalUnits) ?></td>
                <td colspan="6" style="text-align: left; padding-left: 10px; font-size: 10px; color: #4b5563;">
                    Regular Load &bull; Approved by Department Dean
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Bottom: Specified Assessment of Fees & Electronic Receipt -->
    <div class="bottom-grid">
        <!-- Assessment of Fees Breakdown -->
        <div class="fees-box">
            <div class="section-title" style="margin-bottom: 0;">ASSESSMENT OF TUITION &amp; INSTITUTIONAL FEES</div>
            <table class="fees-table">
                <thead>
                    <tr>
                        <th>Particulars / Fee Breakdown Aspect</th>
                        <th style="text-align: right;">Assessed Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($breakdown as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td style="text-align: right; font-weight: 700;"><?= peso($item['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="highlight-total">
                        <td>TOTAL CASH TUITION &amp; FEES:</td>
                        <td style="text-align: right; color: #0b3d2e; font-size: 12px; font-weight: 900;"><?= peso($cashTotal) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #4b5563;">Installment Interest / Surcharge (if deferred):</td>
                        <td style="text-align: right; font-weight: 600; color: #4b5563;"><?= peso($installmentChg) ?></td>
                    </tr>
                    <tr style="background: #fdf2f8;">
                        <td style="font-weight: 800; color: #831843;">TOTAL INSTALLMENT TUITION &amp; FEES:</td>
                        <td style="text-align: right; font-weight: 800; color: #831843;"><?= peso($installmentTot) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Electronic Receipt Verification -->
        <div class="receipt-box">
            <div class="receipt-inner">
                <div class="rcpt-header-line">
                    <span style="font-weight: 800; text-transform: uppercase; font-size: 10.5px;">PAYMENT TRANSACTION RECEIPT</span>
                    <span class="rcpt-or-badge"><?= e($orNumber) ?></span>
                </div>
                <div class="rcpt-row">
                    <span>Payment Channel:</span>
                    <strong><?= e($method) ?></strong>
                </div>
                <div class="rcpt-row">
                    <span>Date &amp; Time Credited:</span>
                    <strong><?= e($paidAt) ?></strong>
                </div>
                <div class="rcpt-row">
                    <span>Description / Aspect:</span>
                    <span><?= e($feeDesc) ?></span>
                </div>
                <div class="rcpt-row" style="border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                    <span>Remaining Term Balance:</span>
                    <strong style="color: <?= $remainingBalance > 0 ? '#b91c1c' : '#047857' ?>; font-size: 12px;">
                        <?= peso($remainingBalance) ?>
                    </strong>
                </div>

                <div class="paid-badge-box">
                    <div class="paid-title">Amount Paid &amp; Credited</div>
                    <div class="paid-amt"><?= peso($amountPaid) ?></div>
                    <div style="font-size: 10px; color: #065f46; font-weight: 600;">&#10003; PayTrack Electronic Payment Verified</div>
                </div>
            </div>

            <div style="background: #f8fafc; border-top: 1px solid #000000; padding: 6px 10px; font-size: 9.5px; color: #4b5563; text-align: center;">
                Certified Official Electronic Record &bull; Valid for Exam Clearance
            </div>
        </div>
    </div>

    <!-- Signatures & Authority -->
    <div class="signatures-row">
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-title"><?= e($studentName) ?></div>
            <div>Student / Guardian Signature</div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-title">OFFICE OF THE REGISTRAR</div>
            <div>Validated &amp; Enrolled</div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-title">ACCOUNTING &amp; CASHIER</div>
            <div>Assessment &amp; Payment Cleared</div>
        </div>
    </div>

    <!-- Action Bar (Hidden when printing) -->
    <div class="actions-bar no-print">
        <button type="button" class="btn-act btn-print" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print / Save COR &amp; Receipt PDF
        </button>
        <a href="<?= APP_URL ?>/public/student/?view=receipts" class="btn-act btn-back">
            &larr; Back to Student Portal
        </a>
    </div>
</div>

</body>
</html>
