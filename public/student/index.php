<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Student.php';
require_once __DIR__ . '/../../models/TuitionFee.php';
require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../models/Refund.php';
require_once __DIR__ . '/../../controllers/StudentController.php';

Auth::start();
Auth::requireRole('student');

$userId = Auth::userId();
$student = Student::findByUserId($userId);

// Live real-time status API for Student Portal sync
if (($_GET['action'] ?? '') === 'live_status') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Student record not found']);
        exit;
    }
    // Re-fetch student record from DB to get updated grade_level and school_year
    $freshStudent = Student::findById((int) $student['id']);
    $studentFees = TuitionFee::getByStudentId((int) $student['id']);
    $primaryFee = !empty($studentFees) ? $studentFees[0] : null;

    $tFees = $primaryFee ? (float)$primaryFee['total_amount'] : 0.0;
    $tPaid = $primaryFee ? (float)$primaryFee['amount_paid'] : 0.0;
    $tRem = max(0.0, $tFees - $tPaid);
    $pct = ($tFees > 0) ? min(100, round(($tPaid / $tFees) * 100)) : 0;
    $studentPayments = Payment::getByStudentId((int) $student['id']);
    $studentRefunds  = Refund::getByStudentId((int) $student['id']);
    $pendingRefunds  = Refund::countPendingByStudentId((int) $student['id']);

    echo json_encode([
        'success' => true,
        'has_assessment' => !empty($primaryFee),
        'fee_id' => $primaryFee ? (int)$primaryFee['id'] : 0,
        'total_amount' => $tFees,
        'amount_paid' => $tPaid,
        'remaining_balance' => $tRem,
        'formatted_total' => peso($tFees),
        'formatted_paid' => peso($tPaid),
        'formatted_remaining' => peso($tRem),
        'percentage' => $pct,
        'status' => $tRem <= 0 ? 'paid' : ($tPaid > 0 ? 'partial' : 'unpaid'),
        'description' => $primaryFee['description'] ?? '',
        'grade_level' => $freshStudent['grade_level'] ?? '',
        'school_year' => $freshStudent['school_year'] ?? '',
        'items' => $primaryFee['items'] ?? [],
        'payments_count' => count($studentPayments),
        'refunds_count' => count($studentRefunds),
        'pending_refunds_count' => $pendingRefunds,
    ]);
    exit;
}

// Routing view: 'home' (default), 'fees', 'history', 'receipts', 'refunds'
$currentView = $_GET['view'] ?? 'home';
if ($currentView === 'password') {
    redirect(APP_URL . '/public/student/?view=home');
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'pay_tuition') {
        StudentController::payTuition();
    } elseif ($action === 'request_refund') {
        StudentController::requestRefund();
    } elseif ($action === 'change_password') {
        StudentController::updatePassword();
    }
}

// Fetch Student Fee Records (with breakdown items)
$fees = $student ? TuitionFee::getByStudentId($student['id']) : [];

$totalFees = 0;
$totalPaid = 0;
foreach ($fees as $f) {
    $totalFees += (float) $f['total_amount'];
    $totalPaid += (float) $f['amount_paid'];
}
$totalRemaining = max(0, $totalFees - $totalPaid);

// Fetch Student Payments History
$payments = $student ? Payment::getByStudentId($student['id']) : [];

// Fetch Student Refund Requests
$refunds = $student ? Refund::getByStudentId((int) $student['id']) : [];
$pendingRefundsCount = $student ? Refund::countPendingByStudentId((int) $student['id']) : 0;
$totalRefunded = $student ? Refund::getTotalApprovedAmountByStudentId((int) $student['id']) : 0.0;

// Flatten fee items across all tuition records for search breakdown
$breakdownItems = [];
foreach ($fees as $f) {
    foreach ($f['items'] ?? [] as $item) {
        $breakdownItems[] = $item;
    }
}

$paymentSuccess = Auth::getFlash('payment_success');
$refundSuccess  = Auth::getFlash('refund_success');
$successMsg = Auth::getFlash('success');
$errorMsg = Auth::getFlash('error');

// Direct Standalone Printable Official Receipt View
if ($currentView === 'receipt') {
    $orNumber = trim($_GET['or'] ?? '');
    $selectedPayment = null;
    foreach ($payments as $p) {
        if ($p['or_number'] === $orNumber) {
            $selectedPayment = $p;
            break;
        }
    }
    if (!$selectedPayment && !empty($payments)) {
        $selectedPayment = $payments[0];
    }
    require_once __DIR__ . '/../../views/student/receipt.php';
    exit;
}

require_once __DIR__ . '/../../views/student/dashboard.php';

