<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Student.php';
require_once __DIR__ . '/../../models/FeeCategory.php';
require_once __DIR__ . '/../../models/TuitionFee.php';
require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../core/Curriculum.php';

Auth::start();
Auth::requireRole('admin');

// Live real-time polling API endpoint for Admin Fee Approvals
if (($_GET['action'] ?? '') === 'live_fee_approvals') {
    header('Content-Type: application/json; charset=utf-8');
    $pending = FeeCategory::getPendingApprovals();
    echo json_encode([
        'success' => true,
        'pending_count' => count($pending),
        'pending_categories' => $pending,
        'hash' => md5(json_encode($pending))
    ]);
    exit;
}

// Handle POST actions with CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create_student':
            AdminController::createStudent();
            break;
        case 'create_accounting':
            AdminController::createAccounting();
            break;
        case 'delete_user':
            AdminController::deleteUser();
            break;
        case 'update_status':
            AdminController::updateUserStatus();
            break;
        case 'approve_fee_category':
            AdminController::approveFeeCategory();
            break;
        case 'reject_fee_category':
            AdminController::rejectFeeCategory();
            break;
    }
}

// Routing view: 'home' (default), 'users', 'logs', 'fee_approvals'
$currentView = $_GET['view'] ?? 'home';
$allowedViews = ['home', 'users', 'logs', 'fee_approvals'];
if (!in_array($currentView, $allowedViews, true)) {
    $currentView = 'home';
}

// Fetch users with live activity and days online tracking
$allUsers = User::getAllWithActivity();

// Summary Metrics
$totalUsers = count($allUsers);
$studentCount = 0;
$accountingCount = 0;
$adminCount = 0;
$onlineCount = 0;
$activeWeekCount = 0;
$now = time();

foreach ($allUsers as $u) {
    if ($u['role'] === 'student') $studentCount++;
    if ($u['role'] === 'accounting') $accountingCount++;
    if ($u['role'] === 'admin') $adminCount++;
    if (!empty($u['is_online'])) $onlineCount++;

    if (!empty($u['last_active_at']) && ($now - strtotime($u['last_active_at']) <= 7 * 86400)) {
        $activeWeekCount++;
    }
}

// Pending Fee Category Approvals
$pendingFeeCategories = FeeCategory::getPendingApprovals();
$pendingFeeCount = count($pendingFeeCategories);
$allFeeCategories = FeeCategory::all();

// Query system-wide email logs with server-side filters
$db = Database::getInstance();
$logSearch = trim($_GET['log_search'] ?? '');
$logType = trim($_GET['log_type'] ?? '');
$logStatus = trim($_GET['log_status'] ?? '');
$logDate = trim($_GET['log_date'] ?? '');

$sql = "SELECT * FROM email_logs WHERE 1=1";
$params = [];

if ($logSearch !== '') {
    $sql .= " AND (recipient_email LIKE ? OR subject LIKE ? OR type LIKE ?)";
    $params[] = "%{$logSearch}%";
    $params[] = "%{$logSearch}%";
    $params[] = "%{$logSearch}%";
}
if ($logType !== '' && $logType !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $logType;
}
if ($logStatus !== '' && $logStatus !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $logStatus;
}
if ($logDate === 'today') {
    $sql .= " AND DATE(sent_at) = CURDATE()";
} elseif ($logDate === '7days') {
    $sql .= " AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($logDate === '30days') {
    $sql .= " AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$sql .= " ORDER BY sent_at DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$emailLogs = $stmt->fetchAll();

// Get total count of email logs unfiltered for badge
$totalLogsCount = (int) $db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();

// Get distinct email types for filter dropdown
$distinctEmailTypes = $db->query("SELECT DISTINCT type FROM email_logs ORDER BY type ASC")->fetchAll(PDO::FETCH_COLUMN);

$successMsg = Auth::getFlash('success');
$errorMsg = Auth::getFlash('error');
$createdCreds = Auth::getFlash('created_student_credentials');
$studentFormData = Auth::getFlash('student_form_data') ?? [];
$studentFormErrors = Auth::getFlash('student_form_errors') ?? [];
$accountCreated = Auth::getFlash('account_created');

require_once __DIR__ . '/../../views/admin/dashboard.php';
