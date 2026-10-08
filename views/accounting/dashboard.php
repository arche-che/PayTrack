<?php
/**
 * PayTrack — Accounting Office Dashboard
 * Manages Student Tuition Fee Assessments, Class Details, Dynamic Fee Breakdown,
 * Manual Payments, Official Receipts, Fee Categories, and Email Logs.
 */
$accountingName = Auth::username() ?? 'accounting';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayTrack — Accounting Portal</title>
    <!-- Bootstrap 5.3 (Local & CDN with Subresource Integrity) -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/bootstrap/css/bootstrap.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- jQuery 3.7.1 for AJAX Operations -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal-chrome.css?v=<?= filemtime(__DIR__ . '/../../assets/css/portal-chrome.css') ?>">
    <style>
        /* ── SweetAlert2 Always on Top of Modals ── */
        .swal2-container {
            z-index: 10000000 !important;
        }

        /* ── Fullscreen Processing / Email Loading Overlay ── */
        .paytrack-loading-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        .paytrack-loading-backdrop.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .paytrack-loading-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 36px 32px;
            width: 90%;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4);
            transform: scale(0.92);
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .paytrack-loading-backdrop.active .paytrack-loading-card {
            transform: scale(1);
        }
        .paytrack-spinner-ring {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 4px solid #d1fae5;
            border-top-color: #059669;
            border-right-color: #0b3d2e;
            animation: paytrackSpin 0.85s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes paytrackSpin {
            to { transform: rotate(360deg); }
        }
        .paytrack-loading-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .paytrack-loading-desc {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.55;
            margin: 0 0 22px;
        }
        .paytrack-loading-bar-wrapper {
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
            margin-bottom: 12px;
        }
        .paytrack-loading-bar-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 40%;
            background: linear-gradient(90deg, #10b981, #0b3d2e);
            border-radius: 999px;
            animation: paytrackBarIndeterminate 1.4s infinite ease-in-out;
        }
        @keyframes paytrackBarIndeterminate {
            0% { left: -40%; width: 40%; }
            50% { left: 30%; width: 60%; }
            100% { left: 100%; width: 40%; }
        }
        .paytrack-inline-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: paytrackSpin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        .btn-account-security {
            width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            margin-bottom: 9px; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 8px; color: #334155; font-size: 13px; font-weight: 650;
            cursor: pointer; transition: background .15s ease, border-color .15s ease;
        }
        .btn-account-security:hover { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-assessed {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .fee-category-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.15s ease;
        }
        .fee-category-row:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }
        .fee-category-row.excluded {
            opacity: 0.5;
            background: #f1f5f9;
        }
        .fee-category-row .cat-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .fee-category-row input[type="number"] {
            width: 130px;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: right;
        }
        .total-computed-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
        }
        .assessment-modal-heading {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: -26px -24px 20px;
            padding: 23px 58px 20px 24px;
            border-bottom: 1px solid #d1fae5;
            background: radial-gradient(circle at 100% 0, rgba(16,185,129,.18), transparent 42%), linear-gradient(135deg, #f0fdf4 0%, #f8fafc 70%);
            border-radius: 14px 14px 0 0;
        }
        .assessment-modal-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            color: #fff;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(145deg, #10b981, #065f46);
            box-shadow: 0 8px 18px rgba(5,150,105,.22);
        }
        .assessment-modal-eyebrow {
            display: block;
            margin-bottom: 4px;
            color: #047857;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.15px;
            text-transform: uppercase;
        }
        .assessment-modal-heading .modal-header-title {
            margin: 0 0 4px;
            font-size: 20px;
            letter-spacing: -.35px;
        }
        .assessment-modal-heading .modal-header-sub {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        .fee-category-row input[type="number"]:invalid,
        .fee-category-row input[type="number"].amount-invalid {
            border-color: #ef4444;
            background: #fff7f7;
            box-shadow: 0 0 0 2px rgba(239,68,68,.1);
        }
        @media (max-width: 560px) {
            .assessment-modal-heading { margin: -20px -14px 18px; padding: 19px 42px 17px 15px; gap: 11px; }
            .assessment-modal-icon { width: 42px; height: 42px; flex-basis: 42px; border-radius: 13px; }
            .assessment-modal-heading .modal-header-title { font-size: 17px; }
        }
        @media print {
            body * { visibility: hidden !important; }
            #receiptModal.active, #receiptModal.active #receiptPrintArea, #receiptModal.active #receiptPrintArea *,
            #assessmentReceiptModal.active, #assessmentReceiptModal.active #assessmentReceiptPrintArea, #assessmentReceiptModal.active #assessmentReceiptPrintArea * {
                visibility: visible !important;
            }
            #assessmentReceiptModal.active, #receiptModal.active {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: #ffffff !important;
            }
            #assessmentReceiptModal.active .modal-window, #receiptModal.active .modal-window {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .modal-close-x, .modal-actions-footer, .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="admin-body">

<div class="app-shell" id="dashboardLayout">
    <!-- Mobile Backdrop Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation (Client-Approved Light Sidebar) -->
    <aside class="sidebar" id="sidebar">
        <div class="org">
            <div class="org-icon">₱</div>
            <div class="org-text">
                <div class="org-name">PayTrack</div>
                <div class="org-team">Accounting Portal</div>
            </div>
            <button class="mobile-menu-btn" id="btnCloseSidebar" style="margin-left: auto; width: 28px; height: 28px; font-size: 14px;">&times;</button>
        </div>

        <ul class="nav">
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=home" class="nav-item <?= $currentView === 'home' ? 'active' : '' ?>">
                    <span class="ic">&#8962;</span> Home
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=students" class="nav-item <?= $currentView === 'students' ? 'active' : '' ?>">
                    <span class="ic">&#127891;</span> Students &amp; Balances
                    <?php if ($pendingAssessmentCount > 0): ?>
                        <span class="badge-count"><?= $pendingAssessmentCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=transactions" class="nav-item <?= $currentView === 'transactions' ? 'active' : '' ?>">
                    <span class="ic">&#8644;</span> Payment Transactions
                    <span class="badge-count"><?= count($payments) ?></span>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=refunds" class="nav-item <?= $currentView === 'refunds' ? 'active' : '' ?>">
                    <span class="ic">&#8635;</span> Refund Requests
                    <?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?>
                        <span class="badge-count" style="background:#d97706;"><?= $pendingRefundsCount ?></span>
                    <?php elseif (!empty($refunds) && count($refunds) > 0): ?>
                        <span class="badge-count"><?= count($refunds) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=fees" class="nav-item <?= $currentView === 'fees' ? 'active' : '' ?>">
                    <span class="ic">&#128179;</span> Tuition Assessments
                    <span class="badge-count"><?= count($fees) ?></span>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=categories" class="nav-item <?= $currentView === 'categories' ? 'active' : '' ?>">
                    <span class="ic">&#9881;</span> Fee Categories
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/accounting/?view=logs" class="nav-item <?= $currentView === 'logs' ? 'active' : '' ?>">
                    <span class="ic">&#9993;</span> Notification Logs
                </a>
            </li>
        </ul>

        <div style="margin-top: auto; padding-top: 18px; border-top: 1px solid #e2e8f0;">
            <button type="button" class="btn-account-security" id="btnOpenPasswordModal">
                <span aria-hidden="true">&#128274;</span><span>Change Password</span>
            </button>
            <button type="button" class="btn-logout-prominent" id="btnLogout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Sign Out / Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main">
        <header class="topbar" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;">
            <button class="mobile-menu-btn" id="btnOpenSidebar" aria-label="Toggle Navigation">&#9776;</button>
            <div class="search-container" style="position: relative;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="accountingSearch" placeholder="Search student, OR#, or assessment..." autocomplete="off">
                <span class="kbd-badge">Ctrl + K</span>
            </div>

            <div class="topbar-actions" style="display: flex; align-items: center; gap: 12px;">
                <?php
                $accNotifs = [];

                // Add Fee Category status notices
                if (!empty($recentCategoryNotifs)) {
                    foreach ($recentCategoryNotifs as $rc) {
                        $rcStatus = $rc['approval_status'] ?? 'approved';
                        if ($rcStatus === 'approved' && !empty($rc['reviewed_at'])) {
                            $accNotifs[] = [
                                'type' => 'success',
                                'icon' => '✓',
                                'title' => 'Fee Category Approved: ' . $rc['name'],
                                'desc' => 'Admin approved ' . $rc['name'] . ' (' . peso($rc['default_amount']) . '). Active for student tuition.',
                                'time' => date('M d, h:i A', strtotime($rc['reviewed_at'])),
                                'link' => APP_URL . '/public/accounting/?view=categories',
                                'unread' => true,
                            ];
                        } elseif ($rcStatus === 'rejected' && !empty($rc['reviewed_at'])) {
                            $accNotifs[] = [
                                'type' => 'danger',
                                'icon' => '✕',
                                'title' => 'Fee Category Rejected: ' . $rc['name'],
                                'desc' => 'Reason: ' . ($rc['rejection_reason'] ?: 'Not approved by admin'),
                                'time' => date('M d, h:i A', strtotime($rc['reviewed_at'])),
                                'link' => APP_URL . '/public/accounting/?view=categories',
                                'unread' => true,
                            ];
                        } elseif ($rcStatus === 'pending') {
                            $accNotifs[] = [
                                'type' => 'warning',
                                'icon' => '⏳',
                                'title' => 'Pending Approval: ' . $rc['name'],
                                'desc' => 'Submitted to Admin for review (' . peso($rc['default_amount']) . ').',
                                'time' => date('M d, h:i A', strtotime($rc['requested_at'] ?? 'now')),
                                'link' => APP_URL . '/public/accounting/?view=categories',
                                'unread' => true,
                            ];
                        }
                    }
                }

                if ($pendingAssessmentCount > 0) {
                    $accNotifs[] = [
                        'type' => 'warning',
                        'icon' => '⏳',
                        'title' => $pendingAssessmentCount . ' student(s) awaiting assessment',
                        'desc' => 'Assign class details and publish tuition fee breakdowns.',
                        'time' => 'Action Required',
                        'link' => APP_URL . '/public/accounting/?view=students',
                        'unread' => true,
                    ];
                }
                $accNotifs[] = [
                    'type' => 'success',
                    'icon' => '₱',
                    'title' => 'Revenue collected: ' . peso($totalRevenue),
                    'desc' => count($payments) . ' verified payments · ' . $collectionRate . '% collection rate',
                    'time' => 'Finance Snapshot',
                    'link' => APP_URL . '/public/accounting/?view=transactions',
                    'unread' => false,
                ];
                if ($totalReceivables > 0) {
                    $accNotifs[] = [
                        'type' => 'info',
                        'icon' => '📊',
                        'title' => 'Outstanding receivables',
                        'desc' => peso($totalReceivables) . ' remaining across active tuition assessments.',
                        'time' => 'Receivables',
                        'link' => APP_URL . '/public/accounting/?view=fees',
                        'unread' => true,
                    ];
                }
                $accUnread = 0;
                foreach ($accNotifs as $n) {
                    if (!empty($n['unread'])) $accUnread++;
                }
                ?>
                <div class="notif-wrapper">
                    <button type="button" class="notif-bell-btn" id="btnAccNotif" aria-label="Notifications" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($accUnread > 0): ?>
                            <span class="notif-dot-red"></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-dropdown-menu" id="accNotifDropdown" role="region" aria-label="Notifications">
                        <div class="notif-header">
                            <div class="notif-header-title">
                                <span>🔔 Notifications</span>
                                <span class="notif-header-badge"><?= $accUnread ?> new</span>
                            </div>
                        </div>
                        <ul class="notif-list-body">
                            <?php foreach ($accNotifs as $notif): ?>
                                <li>
                                    <a href="<?= e($notif['link']) ?>" class="notif-row <?= !empty($notif['unread']) ? 'unread' : '' ?>">
                                        <div class="notif-icon-circle <?= e($notif['type']) ?>"><?= $notif['icon'] ?></div>
                                        <div>
                                            <div class="notif-title"><?= e($notif['title']) ?></div>
                                            <div class="notif-desc"><?= e($notif['desc']) ?></div>
                                            <div class="notif-meta-time"><?= e($notif['time']) ?></div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="notif-footer">
                            <a href="<?= APP_URL ?>/public/accounting/?view=students">Review Students &amp; Balances &rarr;</a>
                        </div>
                    </div>
                </div>

                <div class="user-chip-container">
                    <div class="user-chip-meta">
                        <span class="user-chip-name">Accounting Office</span>
                        <span class="user-chip-id">(<?= e($accountingName) ?>)</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="app-toast success"><span>&#10003;</span> <?= e($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="app-toast error"><span>&#9888;</span> <?= e($errorMsg) ?></div>
        <?php endif; ?>

        <main class="content">

            <!-- ============================================== -->
            <!-- VIEW 0: ACCOUNTING HOME / FINANCIAL OVERVIEW  -->
            <!-- ============================================== -->
            <?php if ($currentView === 'home'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #ecfdf5; color: #059669;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Welcome back, Accounting!</h1>
                            <p class="portal-page-sub">
                                Signed in as <strong><?= e($accountingName) ?></strong> &bull;
                                <?= date('M d, Y') ?> &bull;
                                Finance &amp; tuition operations
                            </p>
                        </div>
                    </div>
                    <?php if ($pendingAssessmentCount > 0): ?>
                        <a href="<?= APP_URL ?>/public/accounting/?view=students" class="btn" style="background:#b45309;color:#fff;font-weight:700;padding:10px 18px;">
                            Review <?= $pendingAssessmentCount ?> Pending &rarr;
                        </a>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/public/accounting/?view=transactions" class="btn" style="background:#0b3d2e;color:#fff;font-weight:700;padding:10px 18px;">
                            Record Payment &rarr;
                        </a>
                    <?php endif; ?>
                </div>

                <div class="metric-summary-grid">
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Net Revenue Collected</div>
                        <div class="metric-summary-value" id="kpiTotalRevenue" style="color:#065f46;"><?= peso($totalRevenue) ?></div>
                        <div class="metric-summary-sub" style="color:#059669;font-weight:600;">
                            <?php if (!empty($totalApprovedRefunds) && $totalApprovedRefunds > 0): ?>
                                Gross: <?= peso($grossRevenue) ?> &bull; Refunded: <?= peso($totalApprovedRefunds) ?>
                            <?php else: ?>
                                <?= count($payments) ?> verified payments
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Total Assessed Tuition</div>
                        <div class="metric-summary-value" id="kpiTotalAssessed" style="color:#1e3a8a;"><?= peso($totalAssessed) ?></div>
                        <div class="metric-summary-sub"><?= count($fees) ?> active term assessments</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Outstanding Receivables</div>
                        <div class="metric-summary-value" id="kpiTotalReceivables" style="color:#ea580c;"><?= peso($totalReceivables) ?></div>
                        <div class="metric-summary-sub" id="kpiCollectionRate"><?= $collectionRate ?>% collection rate</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Enrolled Students</div>
                        <div class="metric-summary-value"><?= count($students) ?></div>
                        <div class="metric-summary-sub" style="font-weight:700;color:<?= $pendingAssessmentCount > 0 ? '#b45309' : '#15803d' ?>;">
                            <?= $pendingAssessmentCount > 0 ? "{$pendingAssessmentCount} awaiting assessment" : 'All assessed' ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?>
                    <div class="section-box" style="background:#fefce8;border-color:#fde047;margin-bottom:18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="font-size:24px;">↺</span>
                                <div>
                                    <h4 style="margin:0;font-size:15px;color:#854d0e;font-weight:700;">Action Required: <?= $pendingRefundsCount ?> Student Refund Request(s) Awaiting Assessment</h4>
                                    <p style="margin:4px 0 0;font-size:12.5px;color:#a16207;">Students and parents have filed tuition refund requests with stated reasons. Review justification and approve or reject.</p>
                                </div>
                            </div>
                            <a href="<?= APP_URL ?>/public/accounting/?view=refunds" class="btn" style="background:#ca8a04;color:#fff;font-weight:700;">Assess Refunds &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($pendingAssessmentCount > 0): ?>
                    <div class="section-box" style="background:#fffbeb;border-color:#fde68a;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                            <div>
                                <h4 style="margin:0;font-size:15px;color:#92400e;font-weight:700;">Action Required: <?= $pendingAssessmentCount ?> Student(s) Awaiting Tuition Assessment</h4>
                                <p style="margin:4px 0 0;font-size:12.5px;color:#b45309;">Set class details, configure fee breakdown, and publish tuition assessments.</p>
                            </div>
                            <a href="<?= APP_URL ?>/public/accounting/?view=students" class="btn" style="background:#b45309;color:#fff;font-weight:700;">Review Students &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="dashboard-2col-grid">
                    <div class="section-box" style="margin-bottom:0;">
                        <div class="section-box-header">
                            <div class="section-header-left">
                                <div class="section-header-icon" style="background:#ecfdf5;color:#059669;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                        <polyline points="17 6 23 6 23 12"></polyline>
                                    </svg>
                                </div>
                                <h2 class="section-box-title">Recent Payment Collections</h2>
                            </div>
                            <a href="<?= APP_URL ?>/public/accounting/?view=transactions" style="font-size:12px;font-weight:600;color:#2563eb;text-decoration:none;">View all &rarr;</a>
                        </div>
                        <div class="table-responsive">
                            <table class="styled-fintech-table">
                                <thead>
                                    <tr>
                                        <th>OR Number</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">No transactions recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($payments, 0, 5) as $p): ?>
                                            <tr>
                                                <td><code style="font-weight:700;"><?= e($p['or_number']) ?></code></td>
                                                <td><strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                                                <td style="color:#059669;font-weight:700;"><?= peso($p['amount']) ?></td>
                                                <td><span class="badge success"><?= strtoupper(e($p['payment_method'])) ?></span></td>
                                                <td><?= date('M d, Y', strtotime($p['paid_at'] ?? $p['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <div class="portal-shortcuts-card">
                            <h4>Accounting Portal Shortcuts</h4>
                            <div class="portal-shortcut-list">
                                <a href="<?= APP_URL ?>/public/accounting/?view=students" class="btn"><span>🎓</span> Students &amp; Balances</a>
                                <a href="<?= APP_URL ?>/public/accounting/?view=transactions" class="btn"><span>💳</span> Payment Transactions</a>
                                <a href="<?= APP_URL ?>/public/accounting/?view=refunds" class="btn">
                                    <span>↺</span> Refund Approvals
                                    <?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?>
                                        <span class="badge" style="margin-left: auto; background: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 7px; border-radius: 99px;"><?= $pendingRefundsCount ?> Pending</span>
                                    <?php endif; ?>
                                </a>
                                <a href="<?= APP_URL ?>/public/accounting/?view=fees" class="btn"><span>📋</span> Tuition Assessments</a>
                                <a href="<?= APP_URL ?>/public/accounting/?view=categories" class="btn"><span>⚙️</span> Fee Categories</a>
                            </div>
                        </div>
                        <div class="portal-advisory-card">
                            <div class="portal-advisory-title"><span>📢</span> Accounting Advisory</div>
                            <p>Publish tuition assessments promptly after enrollment so students can pay online and receive official electronic receipts.</p>
                        </div>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 1: STUDENTS & BALANCES                   -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'students'): ?>

                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background:#ecfdf5;color:#059669;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Students &amp; Tuition Assessments</h1>
                            <p class="portal-page-sub">Assign tuition assessments, update class details, and customize fee categories per student.</p>
                        </div>
                    </div>
                    <?php if ($pendingAssessmentCount > 0): ?>
                        <span class="pill-badge" style="background:#fef3c7;color:#b45309;font-weight:700;padding:7px 14px;border-radius:9999px;font-size:12px;border:1px solid #fde68a;">
                            <?= $pendingAssessmentCount ?> Pending Assessment
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Guide UI Table Card with Controls -->
                <div class="card-box" style="padding: 0; overflow: hidden;">
                    <!-- Table Top Controls -->
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge" style="background: #ecfdf5; color: #059669;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">Student Roster</div>
                                <div style="font-size: 12px; color: #64748b;"><?= count($students) ?> enrolled students</div>
                            </div>
                        </div>
                        <div class="table-top-actions-col">
                            <div class="table-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="studentsTableSearch" placeholder="Search student..." autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- Filter Pill Row -->
                    <div class="pills-and-legend-row" style="padding: 0 20px 12px;">
                        <div class="filter-pills-group" id="studentFilterPills">
                            <button class="filter-tab-pill active" data-filter="all">All Students</button>
                            <button class="filter-tab-pill" data-filter="pending">⏳ Pending Assessment</button>
                            <button class="filter-tab-pill" data-filter="assessed">✓ Assessed</button>
                        </div>
                    </div>

                    <div class="table-responsive" style="padding: 0 20px 20px;">
                        <table class="styled-fintech-table" id="tableStudents">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Class / Section</th>
                                    <th>Assessment Status</th>
                                    <th>Assessed Total</th>
                                    <th>Remaining Balance</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px;">No students enrolled yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $idx => $s): ?>
                                        <?php 
                                            $hasAssmt = !empty($s['has_assessment']);
                                            $totalAmt = $s['primary_fee']['total_amount'] ?? 0;
                                            $paidAmt  = $s['primary_fee']['amount_paid'] ?? 0;
                                            $remAmt   = max(0, $totalAmt - $paidAmt);
                                            $avatarColors = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#ef4444','#06b6d4'];
                                            $avatarColor  = $avatarColors[$idx % count($avatarColors)];
                                            $initials = strtoupper(mb_substr($s['first_name'],0,1) . mb_substr($s['last_name'],0,1));
                                        ?>
                                        <tr data-status="<?= $hasAssmt ? 'assessed' : 'pending' ?>" data-student-id="<?= $s['id'] ?>">
                                            <td style="color:#94a3b8; font-size:12px; width:36px;"><?= $idx + 1 ?></td>
                                            <td><code><?= e($s['student_id']) ?></code></td>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:10px;">
                                                    <div style="width:32px; height:32px; border-radius:50%; background:<?= $avatarColor ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0;"><?= $initials ?></div>
                                                    <div>
                                                        <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                                                        <div style="font-size: 11px; color: #64748b;"><?= e($s['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600; color: #334155;"><?= e($s['grade_level'] ?? '—') ?></span>
                                                <div style="font-size: 11px; color: #94a3b8;">S.Y. <?= e($s['school_year'] ?? '—') ?></div>
                                            </td>
                                            <td>
                                                <?php if ($hasAssmt): ?>
                                                    <span class="status-badge-pill active">✓ Assessed</span>
                                                <?php else: ?>
                                                    <span class="status-badge-pill inactive">⏳ Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $hasAssmt ? '<strong>' . peso($totalAmt) . '</strong>' : '<span style="color:#94a3b8;">—</span>' ?></td>
                                            <td>
                                                <?php if ($hasAssmt): ?>
                                                    <strong class="student-balance-cell" style="color: <?= $remAmt > 0 ? '#b45309' : '#166534' ?>;"><?= peso($remAmt) ?></strong>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" 
                                                    class="btn-primary-blue"
                                                    style="<?= !$hasAssmt ? 'background:#b45309; border-color:#b45309;' : '' ?>"
                                                    onclick="openAssignAssessmentModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                                                    <?= $hasAssmt ? 'Update' : 'Assign Tuition' ?> &rarr;
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                // Students table filter pills
                document.querySelectorAll('#studentFilterPills .filter-tab-pill').forEach(pill => {
                    pill.addEventListener('click', function() {
                        document.querySelectorAll('#studentFilterPills .filter-tab-pill').forEach(p => p.classList.remove('active'));
                        this.classList.add('active');
                        const filter = this.dataset.filter;
                        document.querySelectorAll('#tableStudents tbody tr').forEach(row => {
                            if (filter === 'all') { row.style.display = ''; return; }
                            row.style.display = row.dataset.status === filter ? '' : 'none';
                        });
                    });
                });
                // Inline search for students table
                document.getElementById('studentsTableSearch')?.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    document.querySelectorAll('#tableStudents tbody tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
                </script>

            <!-- ============================================== -->
            <!-- VIEW 2: TRANSACTIONS & MANUAL PAYMENTS        -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'transactions'): ?>

                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background:#eff6ff;color:#2563eb;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Payment Transactions &amp; Receipts</h1>
                            <p class="portal-page-sub">Complete ledger of tuition payments and official receipts generated.</p>
                        </div>
                    </div>
                </div>

                <!-- Table Card with Controls -->
                <div class="card-box" style="padding: 0; overflow: hidden;">
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge" style="background: #eff6ff; color: #2563eb;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">Payment Ledger</div>
                                <div style="font-size: 12px; color: #64748b;"><?= count($payments) ?> transactions recorded</div>
                            </div>
                        </div>
                        <div class="table-top-actions-col">
                            <div class="table-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="txnSearch" placeholder="Search OR#, student..." autocomplete="off">
                            </div>
                            <button type="button" class="btn-primary-blue" onclick="openManualPaymentModal()">
                                + Record Payment
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="padding: 0 20px 20px;">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>OR Number</th>
                                    <th>Student</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Method</th>
                                    <th>Notes / Remarks</th>
                                    <th>Date &amp; Time</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px;">No payment records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td><code style="font-weight: 700;"><?= e($p['or_number']) ?></code></td>
                                            <td>
                                                <strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                                                <div style="font-size: 11px; color: #64748b;"><?= e($p['student_num']) ?></div>
                                            </td>
                                            <td style="color: #059669; font-weight: 700;"><?= peso($p['amount']) ?></td>
                                            <td><span class="badge success"><?= strtoupper(e($p['payment_method'])) ?></span></td>
                                            <td><small style="color: #64748b;"><?= e($p['notes'] ?? 'Tuition installment') ?></small></td>
                                            <td><?= date('M d, Y h:i A', strtotime($p['paid_at'] ?? $p['created_at'])) ?></td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn" style="padding: 5px 10px; font-size: 11.5px;" onclick="viewReceiptVoucher(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                                    <span>🖨 Print OR</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div><!-- /table-responsive -->
                </div><!-- /card-box -->
                <script>
                document.getElementById('txnSearch')?.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    document.querySelectorAll('.styled-fintech-table tbody tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
                </script>

            <!-- ============================================== -->
            <!-- VIEW 3: TUITION ASSESSMENTS DIRECTORY         -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'fees'): ?>

                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background:#fff7ed;color:#ea580c;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Tuition Assessments Directory</h1>
                            <p class="portal-page-sub">Comprehensive list of tuition fee assessments assigned across academic terms.</p>
                        </div>
                    </div>
                </div>

                <!-- Table Card with Controls -->
                <div class="card-box" style="padding: 0; overflow: hidden;">
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge" style="background: #fffbeb; color: #b45309;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">Assessment Records</div>
                                <div style="font-size: 12px; color: #64748b;"><?= count($fees) ?> active assessments</div>
                            </div>
                        </div>
                        <div class="table-top-actions-col">
                            <div class="table-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="feesSearch" placeholder="Search student, assessment..." autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="padding: 0 20px 20px;">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>Assessment ID</th>
                                    <th>Student</th>
                                    <th>Term &amp; Description</th>
                                    <th>Total Assessment</th>
                                    <th>Amount Paid</th>
                                    <th>Remaining Balance</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fees)): ?>
                                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px;">No tuition assessments recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($fees as $f): ?>
                                        <?php $fRem = max(0, (float)$f['total_amount'] - (float)$f['amount_paid']); ?>
                                        <tr data-fee-id="<?= (int) $f['id'] ?>">
                                            <td><code>#TF-<?= $f['id'] ?></code></td>
                                            <td>
                                                <strong><?= e($f['first_name'] . ' ' . $f['last_name']) ?></strong>
                                                <div style="font-size: 11px; color: #64748b;"><?= e($f['student_num']) ?></div>
                                            </td>
                                            <td>
                                                <strong><?= e($f['description']) ?></strong>
                                                <div style="font-size: 11px; color: #94a3b8;">Due: <?= !empty($f['due_date']) ? date('M d, Y', strtotime($f['due_date'])) : 'Open' ?></div>
                                            </td>
                                            <td><strong class="assessment-total"><?= peso($f['total_amount']) ?></strong></td>
                                            <td class="assessment-paid" style="color: #059669; font-weight: 700;"><?= peso($f['amount_paid']) ?></td>
                                            <td class="assessment-remaining" style="color: <?= $fRem > 0 ? '#b45309' : '#166534' ?>; font-weight: 700;"><?= peso($fRem) ?></td>
                                            <td class="assessment-status">
                                                <?php if ($f['status'] === 'paid'): ?>
                                                    <span class="badge success">Paid</span>
                                                <?php elseif ($f['status'] === 'partial'): ?>
                                                    <span class="badge warning">Partial</span>
                                                <?php else: ?>
                                                    <span class="badge danger">Unpaid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn" style="padding: 5px 12px; font-size: 12px; font-weight: 600; color: #0b3d2e; border: 1.5px solid #0b3d2e; background: #f0fdf4; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;"
                                                    onclick="viewAssessmentReceipt(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    View Receipt
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div><!-- /table-responsive -->
                </div><!-- /card-box -->
                <script>
                document.getElementById('feesSearch')?.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    document.querySelectorAll('.styled-fintech-table tbody tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
                </script>

            <!-- ============================================== -->
            <!-- VIEW 4: FEE CATEGORIES CATALOG                -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'categories'): ?>

                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background:#f5f3ff;color:#7c3aed;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Institutional Fee Categories</h1>
                            <p class="portal-page-sub">Configure standard default fee aspects and rates. Accounting staff can modify or exclude them per student.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-primary-blue" onclick="openAddCategoryModal()">+ Add Category</button>
                </div>

                <!-- Table Card -->
                <div class="card-box" style="padding: 0; overflow: hidden;">
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge" style="background: #f5f3ff; color: #7c3aed;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">Fee Category Catalog</div>
                                <div style="font-size: 12px; color: #64748b;"><?= count($feeCategories) ?> configured categories</div>
                            </div>
                        </div>
                        <div class="table-top-actions-col">
                            <button type="button" class="btn-primary-blue" onclick="openAddCategoryModal()">
                                + Add Category
                            </button>
                        </div>
                    </div>
                    <?php $categoriesList = !empty($allFeeCategories) ? $allFeeCategories : $feeCategories; ?>
                    <div class="table-responsive" style="padding: 0 20px 20px;">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>Category Code</th>
                                    <th>Fee Category Name</th>
                                    <th>Default Amount</th>
                                    <th>Type</th>
                                    <th>Approval Status</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categoryTableBody">
                                <?php if (empty($categoriesList)): ?>
                                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 28px;">No fee categories found. Click "+ Add Category" to request one.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($categoriesList as $cat): ?>
                                        <?php $approval = $cat['approval_status'] ?? 'approved'; ?>
                                        <tr>
                                            <td><code><?= e($cat['code']) ?></code></td>
                                            <td><strong><?= e($cat['name']) ?></strong></td>
                                            <td><strong style="color: #0b3d2e;"><?= peso($cat['default_amount']) ?></strong></td>
                                            <td>
                                                <?php if (!empty($cat['is_variable'])): ?>
                                                    <span class="badge info">Variable Remainder</span>
                                                <?php else: ?>
                                                    <span class="badge success">Fixed Aspect</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($approval === 'pending'): ?>
                                                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 600; padding: 4px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true" style="width: 8px; height: 8px;"></span>
                                                        Pending Admin Approval
                                                    </span>
                                                <?php elseif ($approval === 'rejected'): ?>
                                                    <span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 600; padding: 4px 8px; border-radius: 4px; cursor: pointer;"
                                                        data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                                        data-reason="<?= htmlspecialchars($cat['rejection_reason'] ?? '', ENT_QUOTES) ?>"
                                                        onclick="showRejectionReason(this.dataset.name, this.dataset.reason)"
                                                        title="Click to view rejection reason">
                                                        ✕ Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #dcfce7; color: #166534; font-weight: 600; padding: 4px 8px; border-radius: 4px;">
                                                        ✓ Approved &amp; Active
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ($approval === 'pending'): ?>
                                                    <button type="button" class="btn disabled" style="padding: 4px 8px; font-size: 11.5px; opacity: 0.65; cursor: not-allowed; background: #f1f5f9; color: #64748b;" disabled title="Awaiting Admin Review">
                                                        ⏳ Under Review
                                                    </button>
                                                    <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_<?= $cat['id'] ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_fee_category">
                                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                        <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                                                            onclick="confirmDeleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', 'cancel')">Cancel Request</button>
                                                    </form>
                                                <?php elseif ($approval === 'rejected'): ?>
                                                    <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #991b1b; border-color: #fecaca; background: #fff1f2; font-weight: 600;"
                                                        data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                                        data-reason="<?= htmlspecialchars($cat['rejection_reason'] ?? '', ENT_QUOTES) ?>"
                                                        onclick="showRejectionReason(this.dataset.name, this.dataset.reason)">
                                                        Reason
                                                    </button>
                                                    <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_<?= $cat['id'] ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_fee_category">
                                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                        <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                                                            onclick="confirmDeleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px;" onclick="openEditCategoryModal(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">Edit</button>
                                                    <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_<?= $cat['id'] ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_fee_category">
                                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                        <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                                                            onclick="confirmDeleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 5: EMAIL NOTIFICATION AUDIT LOGS         -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'logs'): ?>

                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background:#f1f5f9;color:#334155;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Notification &amp; Email Audit Trail</h1>
                            <p class="portal-page-sub">Historical log of all electronic tuition assessment notices, payment confirmations, and receipts.</p>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card-box" style="padding: 0; overflow: hidden;">
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge" style="background: #f1f5f9; color: #334155;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">Email Audit Log</div>
                                <div style="font-size: 12px; color: #64748b;"><?= count($emailLogs) ?> records found</div>
                            </div>
                        </div>
                        <div class="table-top-actions-col">
                            <div class="table-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="logsSearch" placeholder="Search email, subject..." autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="padding: 0 20px 20px;">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>Recipient Email</th>
                                    <th>Subject</th>
                                    <th>Notification Type</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($emailLogs)): ?>
                                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 32px;">No notification logs recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($emailLogs as $log): ?>
                                        <tr>
                                            <td><strong><?= e($log['recipient_email']) ?></strong></td>
                                            <td><?= e($log['subject']) ?></td>
                                            <td><span class="badge info"><?= e($log['type']) ?></span></td>
                                            <td>
                                                <?php if ($log['status'] === 'sent'): ?>
                                                    <span class="status-badge-pill active">✓ Sent</span>
                                                <?php else: ?>
                                                    <span class="status-badge-pill suspended">✗ Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y h:i A', strtotime($log['sent_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <script>
                document.getElementById('logsSearch')?.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    document.querySelectorAll('.styled-fintech-table tbody tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
                </script>

            <!-- ============================================== -->
            <!-- VIEW 6: REFUND REQUESTS & APPROVALS (ACCOUNTING) -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'refunds'): ?>
                <?php
                    $pendingRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'pending');
                    $approvedRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'approved');
                    $rejectedRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'rejected');
                    $pendingTotalAmt = array_sum(array_map(fn($r) => (float)$r['amount'], $pendingRefundsList));
                ?>
                <!-- PAGE HEADER -->
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #fef3c7; color: #b45309;">
                            <span style="font-size: 22px;">↺</span>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Refund Requests &amp; Approvals</h1>
                            <p class="portal-page-sub">
                                Review student refund requests, assess stated justification reasons, and approve or reject adjustments.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- KPI METRICS -->
                <div class="metric-summary-grid">
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Pending Accounting Review</div>
                        <div class="metric-summary-value" style="color: #d97706;"><?= count($pendingRefundsList) ?></div>
                        <div class="metric-summary-sub" style="font-weight: 600; color: #b45309;">
                            <?= peso($pendingTotalAmt) ?> awaiting assessment
                        </div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Total Approved Refunds</div>
                        <div class="metric-summary-value" style="color: #15803d;"><?= peso($totalApprovedRefunds) ?></div>
                        <div class="metric-summary-sub" style="font-weight: 600; color: #166534;">
                            <?= count($approvedRefundsList) ?> adjustments processed
                        </div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Rejected Requests</div>
                        <div class="metric-summary-value" style="color: #dc2626;"><?= count($rejectedRefundsList) ?></div>
                        <div class="metric-summary-sub">Declined with explanation reason</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Net Revenue Ledger</div>
                        <div class="metric-summary-value" style="color: #065f46;"><?= peso($totalRevenue) ?></div>
                        <div class="metric-summary-sub">Gross <?= peso($grossRevenue) ?> minus refunds</div>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div class="section-box" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
                        <!-- Filter Tabs -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="refundFilterTabs">
                            <button type="button" class="btn active" data-filter="all" onclick="filterRefundTable('all', this)" style="font-size: 12.5px; font-weight: 700; padding: 7px 14px;">
                                All Requests (<?= count($refunds) ?>)
                            </button>
                            <button type="button" class="btn" data-filter="pending" onclick="filterRefundTable('pending', this)" style="font-size: 12.5px; font-weight: 700; padding: 7px 14px;">
                                ⏳ Pending Review (<?= count($pendingRefundsList) ?>)
                            </button>
                            <button type="button" class="btn" data-filter="approved" onclick="filterRefundTable('approved', this)" style="font-size: 12.5px; font-weight: 700; padding: 7px 14px;">
                                ✓ Approved (<?= count($approvedRefundsList) ?>)
                            </button>
                            <button type="button" class="btn" data-filter="rejected" onclick="filterRefundTable('rejected', this)" style="font-size: 12.5px; font-weight: 700; padding: 7px 14px;">
                                ✕ Rejected (<?= count($rejectedRefundsList) ?>)
                            </button>
                        </div>

                        <!-- Search -->
                        <div style="min-width: 260px;">
                            <input type="text" id="refundSearchInput" placeholder="Search student, ID, ref#, OR#..." class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                        </div>
                    </div>

                    <?php if (empty($refunds)): ?>
                        <div style="padding: 48px 20px; text-align: center; color: #64748b;">
                            <div style="font-size: 38px; margin-bottom: 8px; opacity: 0.5;">↺</div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">No Refund Requests Found</h3>
                            <p style="font-size: 13px; margin: 0;">No student refund requests have been submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="styled-fintech-table" id="accountingRefundsTable" style="width: 100%; font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th>Ref Number</th>
                                        <th>Student Details</th>
                                        <th>Assessment / Payment</th>
                                        <th>Refund Amount</th>
                                        <th>Student's Valid Reason</th>
                                        <th>Status</th>
                                        <th>Resolution Details</th>
                                        <th style="text-align: right;">Accounting Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refunds as $ref): ?>
                                        <tr class="refund-row" data-refund-id="<?= (int)$ref['id'] ?>" data-refund='<?= htmlspecialchars(json_encode($ref), ENT_QUOTES, 'UTF-8') ?>' data-status="<?= e($ref['status']) ?>" data-search="<?= strtolower(e($ref['refund_number'] . ' ' . $ref['first_name'] . ' ' . $ref['last_name'] . ' ' . $ref['student_num'] . ' ' . ($ref['or_number'] ?? '') . ' ' . $ref['reason'])) ?>">
                                            <td style="font-family: monospace; font-weight: 700; color: #0b3d2e;">
                                                <?= e($ref['refund_number']) ?>
                                                <div style="font-size: 11px; font-family: sans-serif; color: #94a3b8; font-weight: normal; margin-top: 2px;">
                                                    <?= date('M d, Y h:i A', strtotime($ref['requested_at'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: #0f172a;"><?= e($ref['first_name'] . ' ' . $ref['last_name']) ?></div>
                                                <div style="font-size: 11.5px; color: #64748b;">ID: <span style="font-family: monospace; font-weight: 600;"><?= e($ref['student_num']) ?></span></div>
                                                <div style="font-size: 11px; color: #0369a1;"><?= e($ref['grade_level'] ?? 'BSCS') ?></div>
                                                <div style="font-size: 11px; color: #64748b;"><?= e($ref['email']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #0f172a; max-width: 180px;"><?= e($ref['fee_desc']) ?></div>
                                                <?php if (!empty($ref['or_number'])): ?>
                                                    <div style="font-size: 11.5px; color: #0b3d2e;">
                                                        OR#: <span style="font-family: monospace; font-weight: 700;"><?= e($ref['or_number']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size: 11px; color: #64748b;">Assessed: <?= peso((float)$ref['fee_total']) ?> &bull; Paid: <?= peso((float)$ref['fee_paid']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 15px; font-weight: 800; color: #0f172a;">
                                                    <?= peso((float)$ref['amount']) ?>
                                                </div>
                                            </td>
                                            <td style="max-width: 260px;">
                                                <div style="background: #f8fafc; border-left: 3px solid #cbd5e1; padding: 7px 10px; border-radius: 4px; font-size: 12px; color: #334155; line-height: 1.45; font-style: italic;">
                                                    "<?= e($ref['reason']) ?>"
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($ref['status'] === 'approved'): ?>
                                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 99px; background: #dcfce7; color: #15803d; font-size: 11.5px; font-weight: 700;">
                                                        ✓ Approved
                                                    </span>
                                                <?php elseif ($ref['status'] === 'rejected'): ?>
                                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 99px; background: #fee2e2; color: #991b1b; font-size: 11.5px; font-weight: 700;">
                                                        ✕ Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 99px; background: #fef3c7; color: #92400e; font-size: 11.5px; font-weight: 700;">
                                                        ⏳ Pending Review
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="max-width: 220px; font-size: 12px;">
                                                <?php if ($ref['status'] === 'approved'): ?>
                                                    <div style="color: #15803d; font-weight: 600;">Deducted &amp; Balance Updated</div>
                                                    <?php if (!empty($ref['review_notes'])): ?>
                                                        <div style="font-size: 11.5px; color: #4b5563; margin-top: 2px;">Note: <?= e($ref['review_notes']) ?></div>
                                                    <?php endif; ?>
                                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                                        By: <?= e($ref['reviewer_name'] ?? 'Accounting Staff') ?> (<?= date('M d, Y', strtotime($ref['reviewed_at'])) ?>)
                                                    </div>
                                                <?php elseif ($ref['status'] === 'rejected'): ?>
                                                    <div style="color: #b91c1c; font-weight: 600;">Declined</div>
                                                    <div style="font-size: 11.5px; color: #7f1d1d; background: #fff1f2; padding: 4px 8px; border-radius: 4px; margin-top: 2px;">
                                                        <?= e($ref['rejection_reason']) ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                                        By: <?= e($ref['reviewer_name'] ?? 'Accounting Staff') ?> (<?= date('M d, Y', strtotime($ref['reviewed_at'])) ?>)
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #94a3b8; font-style: italic;">Awaiting Accounting decision</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <?php if ($ref['status'] === 'pending'): ?>
                                                    <div style="display: inline-flex; gap: 6px;">
                                                        <button type="button" class="btn" style="background: #059669; color: #ffffff; font-weight: 700; font-size: 11.5px; padding: 6px 12px; border: none; border-radius: 6px;"
                                                            onclick="openApproveRefundModal(<?= (int)$ref['id'] ?>)">
                                                            ✓ Approve
                                                        </button>
                                                        <button type="button" class="btn" style="background: #ffffff; color: #dc2626; border: 1px solid #fca5a5; font-weight: 700; font-size: 11.5px; padding: 6px 12px; border-radius: 6px;"
                                                            onclick="openRejectRefundModal(<?= (int)$ref['id'] ?>)">
                                                            ✕ Reject
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" class="btn" style="font-size: 11.5px; padding: 5px 10px;"
                                                        onclick="openViewRefundModal(<?= (int)$ref['id'] ?>)">
                                                        👁 Details
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- ACCOUNT SECURITY: CHANGE PASSWORD -->
<div class="modal-backdrop" id="accountPasswordModal" aria-hidden="true">
    <div class="modal-window" style="max-width: 460px;">
        <button type="button" class="modal-close-x" id="btnClosePasswordModal" aria-label="Close">&times;</button>
        <h2 class="modal-header-title" style="font-size:20px;margin-bottom:6px;">Change Password</h2>
        <p class="modal-header-sub" style="margin:0 0 20px;color:#64748b;">Confirm your current password to secure your Accounting account.</p>
        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=home" id="accountPasswordForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label" for="currentPassword">Current Password</label>
                <input class="form-control" type="password" name="current_password" id="currentPassword" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="newPassword">New Password</label>
                <input class="form-control" type="password" name="new_password" id="newPassword" minlength="8" autocomplete="new-password" required>
                <small style="display:block;margin-top:5px;color:#64748b;">Use at least 8 characters.</small>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" for="confirmPassword">Confirm New Password</label>
                <input class="form-control" type="password" name="confirm_password" id="confirmPassword" minlength="8" autocomplete="new-password" required>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn" id="btnCancelPasswordModal">Cancel</button>
                <button type="submit" class="btn" style="background:#0b3d2e;color:#fff;font-weight:700;">Update Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: ASSIGN TUITION & CLASS DETAILS WITH EDITABLE FEES -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="assignTuitionModal">
    <div class="modal-window" style="max-width: 620px;">
        <button class="modal-close-x" id="btnCloseAssignModal">&times;</button>
        <div class="assessment-modal-heading">
            <div class="assessment-modal-icon" aria-hidden="true">₱</div>
            <div>
                <span class="assessment-modal-eyebrow">Tuition Management</span>
                <h2 class="modal-header-title">Assessment &amp; Class Details</h2>
                <p class="modal-header-sub" id="assignModalStudentLabel">Student: Juan Dela Cruz (2023-53512)</p>
            </div>
        </div>

        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=students" id="assignTuitionForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="assign_tuition">
            <input type="hidden" name="student_id" id="assignStudentId" value="">
            <input type="hidden" name="fee_id" id="assignFeeId" value="">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group">
                    <label class="form-label" for="assignGradeLevel">Class / Program &amp; Section *</label>
                    <input type="text" class="form-control" name="grade_level" id="assignGradeLevel" required placeholder="e.g. BSCS 11A1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="assignSchoolYear">School Year *</label>
                    <input type="text" class="form-control" name="school_year" id="assignSchoolYear" required value="<?= date('Y') . '-' . (date('Y') + 1) ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group">
                    <label class="form-label" for="assignSemester">Semester / Term *</label>
                    <select class="form-control" name="semester" id="assignSemester" required onchange="updateAssessmentDesc()">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="assignDueDate">Assessment Due Date</label>
                    <input type="date" class="form-control" name="due_date" id="assignDueDate" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="assignDescription">Assessment Description *</label>
                <input type="text" class="form-control" name="description" id="assignDescription" required value="S.Y. <?= date('Y') . '-' . (date('Y') + 1) ?> - 1st Semester Tuition">
            </div>

            <!-- Dynamic Fee Categories Checklist & Rate Customization -->
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label class="form-label" style="margin: 0; font-weight: 700;">Fee Breakdown &amp; Institutional Aspects</label>
                    <small style="color: #64748b;">Institutional fees are fixed; adjust subject tuition as needed</small>
                </div>

                <div id="feeCategoriesContainer" style="max-height: 240px; overflow-y: auto; padding-right: 4px;">
                    <?php foreach ($feeCategories as $cat): ?>
                        <div class="fee-category-row" id="catRow_<?= $cat['id'] ?>">
                            <div class="cat-info">
                                <input type="checkbox" 
                                       name="fee_included[<?= $cat['id'] ?>]" 
                                       id="chk_<?= $cat['id'] ?>" 
                                       value="1" 
                                       checked 
                                       onchange="toggleCategoryRow(<?= $cat['id'] ?>)">
                                <label for="chk_<?= $cat['id'] ?>" style="margin: 0; font-size: 13px; font-weight: 600; cursor: pointer;">
                                    <?= e($cat['name']) ?>
                                </label>
                                <input type="hidden" name="fee_name[<?= $cat['id'] ?>]" value="<?= e($cat['name']) ?>">
                            </div>
                            <div>
                                <span style="font-size: 12px; color: #64748b; margin-right: 4px;">₱</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="fee_amount[<?= $cat['id'] ?>]" 
                                       id="amt_<?= $cat['id'] ?>" 
                                       data-variable="<?= !empty($cat['is_variable']) ? '1' : '0' ?>"
                                       data-default-amount="<?= number_format($cat['default_amount'], 2, '.', '') ?>"
                                       value="<?= number_format($cat['default_amount'], 2, '.', '') ?>" 
                                       inputmode="decimal"
                                       required
                                       
                                       <?= !empty($cat['is_variable']) ? 'placeholder="Calculated from assessment total"' : '' ?>
                                       aria-label="<?= e($cat['name']) ?> amount, maximum two decimal places"
                                       oninput="recalcAssessmentTotal()">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total Computed -->
                <div class="total-computed-box">
                    <div>
                        <span style="font-size: 12px; color: #047857; font-weight: 700; text-transform: uppercase;">Total Tuition Assessment</span>
                        <div style="font-size: 11px; color: #065f46;">Auto-calculated sum of included fees</div>
                    </div>
                    <div style="font-size: 22px; font-weight: 900; color: #047857;" id="computedTotalDisplay">₱0.00</div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="notify_email" value="1" checked>
                    <span><strong>Send automated email notification</strong> with fee breakdown to Student &amp; Parents</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" id="btnCancelAssignModal">Cancel</button>
                <button type="submit" class="btn" id="btnSubmitAssignModal" style="background: #0b3d2e; color: #fff; font-weight: 700; padding: 10px 22px;">
                    Post &amp; Save Assessment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: RECORD MANUAL PAYMENT                         -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="manualPaymentModal">
    <div class="modal-window" style="max-width: 480px;">
        <button class="modal-close-x" id="btnCloseManualPay">&times;</button>
        <h2 class="modal-header-title">Record Counter Payment</h2>
        <p class="modal-header-sub">Process cash, GCash, or bank deposit at Accounting Cashier.</p>

        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=transactions" id="manualPaymentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="record_payment">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="payFeeId">Select Student Assessment *</label>
                <select class="form-control" name="fee_id" id="payFeeId" required>
                    <option value="" data-balance="0">-- Choose student assessment --</option>
                    <?php foreach ($fees as $feeItem): ?>
                        <?php $rem = max(0, (float)$feeItem['total_amount'] - (float)$feeItem['amount_paid']); ?>
                        <?php if ($rem > 0): ?>
                            <option value="<?= $feeItem['id'] ?>" data-balance="<?= $rem ?>" data-student="<?= e($feeItem['first_name'] . ' ' . $feeItem['last_name']) ?>">
                                <?= e($feeItem['first_name'] . ' ' . $feeItem['last_name']) ?> (<?= e($feeItem['student_num']) ?>) — Balance: <?= peso($rem) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div id="payFeeBalanceHint" style="font-size: 12px; color: #b45309; font-weight: 600; margin-top: 4px; display: none;"></div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="payAmount">Payment Amount (₱) *</label>
                <input type="number" step="0.01" min="1" class="form-control" name="amount" id="payAmount" required placeholder="0.00">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="payMethod">Payment Method *</label>
                <select class="form-control" name="payment_method" id="payMethod" required>
                    <option value="cash">Cash (Over-the-Counter)</option>
                    <option value="gcash">GCash</option>
                    <option value="maya">Maya</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Debit / Credit Card</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="payNotes">Notes / Reference No</label>
                <input type="text" class="form-control" name="notes" id="payNotes" placeholder="e.g. Cashier Window 2, GCash Ref #12345">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('manualPaymentModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn" id="btnSubmitManualPay" style="background: #0b3d2e; color: #fff; font-weight: 700;">Record &amp; Issue Receipt</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- FULLSCREEN ACCOUNTING ACTION LOADING MODAL -->
<!-- ========================================== -->
<div class="paytrack-loading-backdrop" id="accountingLoadingOverlay">
    <div class="paytrack-loading-card">
        <div class="paytrack-spinner-ring"></div>
        <div class="paytrack-loading-title" id="accountingLoadingTitle">Processing Transaction...</div>
        <p class="paytrack-loading-desc" id="accountingLoadingDesc">
            Please wait while records are updated and notification emails are being delivered.
        </p>
        <div class="paytrack-loading-bar-wrapper">
            <div class="paytrack-loading-bar-fill"></div>
        </div>
        <small style="color: #94a3b8; font-size: 11px;">Do not close or refresh this page.</small>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: ADD / EDIT FEE CATEGORY                       -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="categoryModal">
    <div class="modal-window" style="max-width: 440px;">
        <button class="modal-close-x" onclick="document.getElementById('categoryModal').classList.remove('active')">&times;</button>
        <h2 class="modal-header-title" id="catModalTitle" style="margin-bottom: 2px;">Request New Fee Category</h2>
        <p class="modal-header-sub" id="catModalSub" style="color: #64748b; font-size: 12px; margin: 0 0 16px;">Submit a new fee aspect for Administrator review and approval.</p>
        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" id="categoryForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_fee_category">
            <input type="hidden" name="category_id" id="catModalId" value="0">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="catModalName">Category Name *</label>
                <input type="text" class="form-control" name="name" id="catModalName" required placeholder="e.g. Laboratory Fee">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="catModalAmount">Default Amount (₱) *</label>
                <input type="number" step="0.01" min="0" class="form-control" name="default_amount" id="catModalAmount" required placeholder="0.00">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                    <input type="checkbox" name="is_variable" id="catModalVariable" value="1">
                    <span>Variable Aspect (receives tuition remainder)</span>
                </label>
            </div>

            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="catModalSort">Sort Order</label>
                <input type="number" class="form-control" name="sort_order" id="catModalSort" value="10">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('categoryModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn" id="btnSubmitCategory" style="background: #0b3d2e; color: #fff; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <span id="btnSubmitCategoryText">Request to Admin</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- ==================================================== -->
<!-- MODAL: OFFICIAL STUDENT TUITION ASSESSMENT RECEIPT   -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="assessmentReceiptModal">
    <div class="modal-window" style="max-width: 680px; max-height: 90vh; overflow-y: auto;">
        <button class="modal-close-x" onclick="document.getElementById('assessmentReceiptModal').classList.remove('active')">&times;</button>
        
        <div id="assessmentReceiptPrintArea" style="padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a;">
            <!-- Header Letterhead -->
            <div style="text-align: center; border-bottom: 2px solid #0b3d2e; padding-bottom: 14px; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.5px; color: #047857; text-transform: uppercase;">Accounting Office — Student Financial Services</div>
                <h2 style="margin: 4px 0 2px; color: #0b3d2e; font-size: 20px; font-weight: 800;">NATIONAL COLLEGE OF SCIENCE AND TECHNOLOGY</h2>
                <div style="font-size: 13px; font-weight: 600; color: #334155;">Official Student Tuition Assessment Statement &amp; Receipt</div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-top: 10px; padding: 0 4px;">
                    <span>Ref No: <strong id="arNumber" style="color: #0b3d2e;">#TF-0000</strong></span>
                    <span>Date Generated: <span id="arDate"><?= date('M d, Y') ?></span></span>
                </div>
            </div>

            <!-- Student Info Card -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12.5px;">
                <div>
                    <span style="color: #64748b;">Student Name:</span><br>
                    <strong id="arStudentName" style="font-size: 14px; color: #0f172a;">—</strong>
                </div>
                <div>
                    <span style="color: #64748b;">Student ID / Number:</span><br>
                    <strong id="arStudentNum" style="font-size: 14px; color: #0b3d2e;">—</strong>
                </div>
                <div>
                    <span style="color: #64748b;">Course / Program &amp; Year:</span><br>
                    <span id="arGradeLevel" style="font-weight: 600;">—</span>
                </div>
                <div>
                    <span style="color: #64748b;">School Year &amp; Semester:</span><br>
                    <span id="arTerm" style="font-weight: 600;">—</span>
                </div>
                <div style="grid-column: span 2; border-top: 1px dashed #cbd5e1; padding-top: 8px; margin-top: 2px;">
                    <span style="color: #64748b;">Assessment Description:</span>
                    <strong id="arDescription" style="color: #1e293b;">—</strong>
                </div>
            </div>

            <!-- Fee Breakdown Table -->
            <div style="margin-bottom: 20px;">
                <div style="font-size: 13px; font-weight: 700; color: #0b3d2e; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Itemized Fee Assessment Breakdown</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                    <thead>
                        <tr style="background: #f1f5f9; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                            <th style="text-align: left; padding: 8px 10px; color: #475569;">Fee Aspect / Particulars</th>
                            <th style="text-align: right; padding: 8px 10px; color: #475569;">Assessed Amount</th>
                        </tr>
                    </thead>
                    <tbody id="arItemsBody">
                        <tr><td colspan="2" style="padding: 10px; text-align: center; color: #94a3b8;">No fee items recorded.</td></tr>
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid #0b3d2e; font-weight: 800; font-size: 13px;">
                            <td style="padding: 10px; text-align: right; color: #0b3d2e;">TOTAL TUITION ASSESSMENT:</td>
                            <td style="padding: 10px; text-align: right; color: #0b3d2e;" id="arTotalAssessed">₱0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Transactions / Official Receipts -->
            <div style="margin-bottom: 20px;">
                <div style="font-size: 13px; font-weight: 700; color: #059669; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Official Payment Transactions &amp; Receipts</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background: #f0fdf4; border-top: 1px solid #bbf7d0; border-bottom: 1px solid #bbf7d0;">
                            <th style="text-align: left; padding: 6px 10px; color: #166534;">OR #</th>
                            <th style="text-align: left; padding: 6px 10px; color: #166534;">Date &amp; Time</th>
                            <th style="text-align: left; padding: 6px 10px; color: #166534;">Method</th>
                            <th style="text-align: right; padding: 6px 10px; color: #166534;">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody id="arPaymentsBody">
                        <tr><td colspan="4" style="padding: 8px 10px; text-align: center; color: #94a3b8;">No payments logged yet for this assessment.</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary Box -->
            <div style="background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; text-align: center;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #065f46;">Total Assessed</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0b3d2e; margin-top: 2px;" id="arSumAssessed">₱0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #065f46;">Total Paid</div>
                        <div style="font-size: 18px; font-weight: 800; color: #059669; margin-top: 2px;" id="arSumPaid">₱0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #065f46;">Balance Due</div>
                        <div style="font-size: 18px; font-weight: 800; color: #dc2626; margin-top: 2px;" id="arSumRemaining">₱0.00</div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #6ee7b7; font-size: 12px;">
                    <span>Account Status: </span><strong id="arStatusBadge" style="display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px;">UNPAID</strong>
                </div>
            </div>

            <!-- Official Certification & Signatures -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 26px; padding-top: 16px; border-top: 1px solid #cbd5e1; font-size: 11.5px; color: #64748b;">
                <div>
                    <div>PayTrack Certified Electronic Assessment &amp; Receipt</div>
                    <div>National College of Science and Technology</div>
                </div>
                <div style="text-align: center; width: 170px;">
                    <div style="border-bottom: 1px solid #334155; margin-bottom: 4px; height: 26px;"></div>
                    <strong style="color: #0f172a;">Accounting Officer</strong><br>
                    <span>Authorized Signature</span>
                </div>
            </div>
        </div>

        <!-- Modal Actions Footer -->
        <div class="modal-actions-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; border-radius: 0 0 16px 16px;">
            <button type="button" class="btn" onclick="document.getElementById('assessmentReceiptModal').classList.remove('active')">Close</button>
            <button type="button" class="btn" style="background: #0b3d2e; color: #fff; display: inline-flex; align-items: center; gap: 6px;" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print Official Receipt / Statement
            </button>
        </div>
    </div>
</div>

<!-- MODAL: PRINTABLE OFFICIAL RECEIPT                    -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="receiptModal">
    <div class="modal-window" style="max-width: 540px;">
        <button class="modal-close-x" onclick="document.getElementById('receiptModal').classList.remove('active')">&times;</button>
        <div id="receiptPrintArea" style="padding: 20px; font-family: sans-serif;">
            <div style="text-align: center; border-bottom: 2px solid #0b3d2e; padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="margin: 0; color: #0b3d2e; font-size: 18px;">PayTrack — Official Payment Receipt</h3>
                <div style="font-size: 12px; color: #64748b;">National College of Science and Technology</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Official Receipt No:</span>
                <strong id="vOrNumber">OR-00000</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Student Name:</span>
                <strong id="vStudentName">—</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Student ID:</span>
                <span id="vStudentNum">—</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Payment Method:</span>
                <strong id="vMethod">CASH</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 14px;">
                <span>Date &amp; Time:</span>
                <span id="vDate">—</span>
            </div>
            <div style="background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 8px; padding: 14px; text-align: center; margin-bottom: 16px;">
                <div style="font-size: 11px; font-weight: bold; color: #065f46; text-transform: uppercase;">Amount Received</div>
                <div style="font-size: 26px; font-weight: 900; color: #059669;" id="vAmount">₱0.00</div>
                <div style="font-size: 11px; color: #047857; margin-top: 4px;">Verified &amp; Recorded by Accounting Cashier</div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b;">
                <div>Accounting Office Copy<br>System Generated</div>
                <div style="text-align: right; border-top: 1px solid #334155; width: 140px; padding-top: 2px;">Authorized Signature</div>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; padding: 0 20px 14px;">
            <button type="button" class="btn" onclick="document.getElementById('receiptModal').classList.remove('active')">Close</button>
            <button type="button" class="btn" style="background: #0b3d2e; color: #fff;" onclick="window.print()">Print Voucher</button>
        </div>
    </div>
</div>
<!-- ============================================== -->
<!-- MODAL: APPROVE REFUND REQUEST                 -->
<!-- ============================================== -->
<div class="modal-backdrop" id="approveRefundModal" style="display: none;">
    <div class="modal-window" style="max-width: 520px;">
        <button type="button" class="modal-close-x" onclick="closeApproveRefundModal()">&times;</button>
        <h2 class="modal-header-title" style="color: #065f46;">Assess &amp; Approve Refund</h2>
        <p class="modal-header-sub" style="font-size: 13px; color: #64748b; margin: 2px 0 16px;">
            Verify the student's justification reason and approve tuition balance adjustment.
        </p>

        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=refunds" id="approveRefundForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve_refund">
            <input type="hidden" name="refund_id" id="approveRefundId">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b;">Student:</span>
                    <strong id="approveStudentName" style="color: #0f172a;"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b;">Student ID:</span>
                    <span id="approveStudentNum" style="font-family: monospace; font-weight: 700;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b;">Ref Number:</span>
                    <span id="approveRefundNum" style="font-family: monospace; font-weight: 700; color: #0b3d2e;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #64748b;">Assessment:</span>
                    <span id="approveFeeDesc" style="font-weight: 600;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                    <span style="font-weight: 700; color: #0f172a;">Refund Amount:</span>
                    <span id="approveRefundAmount" style="font-size: 20px; font-weight: 800; color: #15803d;"></span>
                </div>
            </div>

            <!-- Student Stated Reason -->
            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Student Stated Reason:</label>
                <div id="approveStudentReason" style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 3px solid #0b3d2e; border-radius: 6px; padding: 10px 12px; font-size: 12.5px; color: #334155; line-height: 1.45; font-style: italic; margin-top: 4px;"></div>
            </div>

            <!-- System Effects Callout -->
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 12px; color: #065f46; line-height: 1.5;">
                <strong style="color: #047857;">On Approval:</strong>
                <ul style="margin: 4px 0 0; padding-left: 18px;">
                    <li>Amount is deducted from student's paid tuition, adjusting remaining balance.</li>
                    <li>Accounting Net Revenue and collections reflect this adjustment.</li>
                    <li>Official confirmation notice is emailed to student (<span id="approveStudentEmail" style="font-weight: 700;"></span>) and parent (<span id="approveParentEmail" style="font-weight: 700;"></span>).</li>
                </ul>
            </div>

            <!-- Accounting Remarks -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="approveNotes" style="font-size: 13px; font-weight: 700;">Accounting Remarks / Notes (Optional)</label>
                <textarea class="form-control" name="notes" id="approveNotes" rows="2" placeholder="e.g. Approved and processed via Cashier OTC / check disbursement." style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeApproveRefundModal()" class="btn" style="flex: 1; justify-content: center; padding: 10px;">Cancel</button>
                <button type="submit" class="btn" style="flex: 2; justify-content: center; padding: 10px; background: #059669; color: #ffffff; font-weight: 700; border: none; border-radius: 8px;">
                    ✓ Confirm &amp; Approve Refund
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: REJECT REFUND REQUEST                  -->
<!-- ============================================== -->
<div class="modal-backdrop" id="rejectRefundModal" style="display: none;">
    <div class="modal-window" style="max-width: 480px;">
        <button type="button" class="modal-close-x" onclick="closeRejectRefundModal()">&times;</button>
        <h2 class="modal-header-title" style="color: #991b1b;">Reject Refund Request</h2>
        <p class="modal-header-sub" style="font-size: 13px; color: #64748b; margin: 2px 0 16px;">
            Provide an official reason for declining the student's refund request.
        </p>

        <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=refunds" id="rejectRefundForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject_refund">
            <input type="hidden" name="refund_id" id="rejectRefundId">

            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: #7f1d1d;">Student:</span>
                    <strong id="rejectStudentName" style="color: #991b1b;"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: #7f1d1d;">Ref Number:</span>
                    <span id="rejectRefundNum" style="font-family: monospace; font-weight: 700;"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #7f1d1d;">Requested Amount:</span>
                    <strong id="rejectRefundAmount" style="color: #b91c1c;"></strong>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="rejectionReasonInput" style="font-size: 13px; font-weight: 700; color: #0f172a;">Official Reason for Rejection *</label>
                <textarea class="form-control" name="rejection_reason" id="rejectionReasonInput" rows="3" required minlength="5" placeholder="Explain why this refund cannot be granted (e.g., student did not submit dropping slip within prescribed deadline)..." style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;"></textarea>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">This explanation will be emailed to the student and parent.</div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeRejectRefundModal()" class="btn" style="flex: 1; justify-content: center; padding: 10px;">Cancel</button>
                <button type="submit" class="btn" style="flex: 2; justify-content: center; padding: 10px; background: #dc2626; color: #ffffff; font-weight: 700; border: none; border-radius: 8px;">
                    ✕ Reject Refund Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: VIEW REFUND DETAILS                    -->
<!-- ============================================== -->
<div class="modal-backdrop" id="viewRefundModal" style="display: none;">
    <div class="modal-window" style="max-width: 520px;">
        <button type="button" class="modal-close-x" onclick="closeViewRefundModal()">&times;</button>
        <h2 class="modal-header-title">Refund Request Details</h2>
        <p class="modal-header-sub" id="viewRefundSub" style="font-size: 13px; color: #64748b; margin: 2px 0 16px;"></p>

        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: #64748b;">Student:</span>
                    <strong id="viewStudentName"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: #64748b;">Student ID:</span>
                    <span id="viewStudentNum" style="font-family: monospace; font-weight: 600;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: #64748b;">Assessment:</span>
                    <span id="viewFeeDesc"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Related Receipt:</span>
                    <span id="viewORNum" style="font-family: monospace;"></span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                <span style="font-weight: 700; color: #0f172a;">Refund Amount:</span>
                <span id="viewRefundAmount" style="font-size: 18px; font-weight: 800; color: #0b3d2e;"></span>
            </div>

            <div>
                <label style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Student Stated Reason:</label>
                <div id="viewStudentReason" style="background: #f8fafc; border-left: 3px solid #cbd5e1; padding: 10px 12px; font-size: 12.5px; color: #334155; line-height: 1.45; font-style: italic; border-radius: 4px; margin-top: 4px;"></div>
            </div>

            <div id="viewResolutionBox" style="border-radius: 8px; padding: 12px 14px;"></div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
            <button type="button" onclick="closeViewRefundModal()" class="btn" style="padding: 9px 18px;">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ── Drawer Mobile Toggle ──
    const btnOpenSidebar = document.getElementById('btnOpenSidebar');
    const btnCloseSidebar = document.getElementById('btnCloseSidebar');
    const sidebar = document.getElementById('sidebar') || document.getElementById('sidebarNav');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (btnOpenSidebar && sidebar && sidebarOverlay) {
        btnOpenSidebar.addEventListener('click', () => {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        const closeSidebar = () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        };
        btnCloseSidebar && btnCloseSidebar.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // ── Change Password Modal ──
    const accountPasswordModal = document.getElementById('accountPasswordModal');
    const closePasswordModal = () => {
        accountPasswordModal?.classList.remove('active');
        accountPasswordModal?.setAttribute('aria-hidden', 'true');
    };
    document.getElementById('btnOpenPasswordModal')?.addEventListener('click', () => {
        accountPasswordModal?.classList.add('active');
        accountPasswordModal?.setAttribute('aria-hidden', 'false');
        document.getElementById('currentPassword')?.focus();
    });
    document.getElementById('btnClosePasswordModal')?.addEventListener('click', closePasswordModal);
    document.getElementById('btnCancelPasswordModal')?.addEventListener('click', closePasswordModal);
    accountPasswordModal?.addEventListener('click', event => {
        if (event.target === accountPasswordModal) closePasswordModal();
    });
    document.getElementById('accountPasswordForm')?.addEventListener('submit', event => {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        confirmPassword.setCustomValidity(newPassword.value === confirmPassword.value ? '' : 'The passwords do not match.');
        if (!confirmPassword.reportValidity()) event.preventDefault();
    });
    document.getElementById('confirmPassword')?.addEventListener('input', function () {
        this.setCustomValidity(this.value === document.getElementById('newPassword').value ? '' : 'The passwords do not match.');
    });

    // Logout
    document.getElementById('btnLogout')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Sign Out?',
            text: 'Are you sure you want to end your accounting session?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0b3d2e',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Sign Out'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= APP_URL ?>/public/?action=logout';
            }
        });
    });

    // ── SweetAlert: Delete Tuition Assessment ──
    window.confirmDeleteFee = function(feeId, description) {
        Swal.fire({
            title: 'Delete Assessment?',
            html: `Are you sure you want to permanently delete the assessment:<br><br><strong>${description}</strong><br><br><span style="color:#ef4444;font-size:12px;">⚠ This will also delete all payment records linked to this assessment. This action cannot be undone.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Delete Assessment',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteFeeForm_' + feeId)?.submit();
            }
        });
    };

    // ── SweetAlert: Delete Fee Category ──
    window.confirmDeleteCategory = function(catId, name, actionType = 'delete') {
        const isCancel = actionType === 'cancel';
        Swal.fire({
            title: isCancel ? 'Cancel Approval Request?' : 'Delete Fee Category?',
            html: isCancel 
                ? `Cancel pending request for <strong>${name}</strong>? Admin review will be withdrawn.`
                : `Are you sure you want to delete the category:<br><br><strong>${name}</strong><br><br><span style="color:#ef4444;font-size:12px;">⚠ This will fail if existing assessments still use this category.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: isCancel ? 'Yes, Cancel Request' : 'Yes, Delete Category',
            cancelButtonText: 'Back'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteCatForm_' + catId)?.submit();
            }
        });
    };

    // ── Open Assign Assessment Modal ──
    const assignTuitionModal = document.getElementById('assignTuitionModal');
    const btnCloseAssignModal = document.getElementById('btnCloseAssignModal');
    const btnCancelAssignModal = document.getElementById('btnCancelAssignModal');

    window.openAssignAssessmentModal = function(student) {
        if (!assignTuitionModal) return;
        const currentFee = student.primary_fee || null;
        document.getElementById('assignStudentId').value = student.id;
        document.getElementById('assignFeeId').value = currentFee ? currentFee.id : '';
        document.getElementById('assignModalStudentLabel').textContent = 
            `Student: ${student.first_name} ${student.last_name} (${student.student_id})`;
        document.getElementById('assignGradeLevel').value = student.grade_level || 'BSCS 11A1';
        document.getElementById('assignSchoolYear').value = currentFee?.school_year || student.school_year || '<?= date('Y') . '-' . (date('Y') + 1) ?>';
        const semesterInput = document.getElementById('assignSemester');
        const supportedSemesters = Array.from(semesterInput.options).map(option => option.value);
        semesterInput.value = currentFee && supportedSemesters.includes(currentFee.semester) ? currentFee.semester : '1st Semester';
        updateAssessmentDesc();
        if (currentFee) {
            document.getElementById('assignDescription').value = currentFee.description || document.getElementById('assignDescription').value;
            document.getElementById('assignDueDate').value = currentFee.due_date || '';

            // Reopen the existing item breakdown, so its sum stays equal to the
            // current assessment total instead of resetting to category defaults.
            const existingItems = Array.isArray(currentFee.items) ? currentFee.items : [];
            document.querySelectorAll('.fee-category-row').forEach(row => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                const amount = row.querySelector('input[type="number"]');
                const categoryId = Number((checkbox.name.match(/\[(\d+)\]/) || [])[1]);
                const categoryName = row.querySelector('label')?.textContent.trim();
                const item = existingItems.find(existing => Number(existing.fee_category_id) === categoryId)
                    || existingItems.find(existing => !existing.fee_category_id && existing.category_name === categoryName);
                checkbox.checked = Boolean(item);
                // Load existing assessed item amount directly so adjustments made by accounting reflect accurately
                amount.value = item ? Number(item.amount).toFixed(2) : Number(amount.dataset.defaultAmount).toFixed(2);
                amount.disabled = !item;
                row.classList.toggle('excluded', !item);
                validateAssessmentAmount(amount);
            });
        } else {
            document.getElementById('assignDueDate').value = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
            document.querySelectorAll('.fee-category-row').forEach(row => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                const amount = row.querySelector('input[type="number"]');
                checkbox.checked = true;
                amount.disabled = false;
                row.classList.remove('excluded');
                amount.value = amount.dataset.variable === '1' ? '0.00' : Number(amount.dataset.defaultAmount).toFixed(2);
                validateAssessmentAmount(amount);
            });
        }
        recalcAssessmentTotal();
        assignTuitionModal.classList.add('active');
    };

    function closeAssignModal() {
        assignTuitionModal && assignTuitionModal.classList.remove('active');
    }
    btnCloseAssignModal && btnCloseAssignModal.addEventListener('click', closeAssignModal);
    btnCancelAssignModal && btnCancelAssignModal.addEventListener('click', closeAssignModal);

    window.updateAssessmentDesc = function() {
        const sy = document.getElementById('assignSchoolYear').value.trim() || '<?= date('Y') . '-' . (date('Y') + 1) ?>';
        const sem = document.getElementById('assignSemester').value;
        const descInput = document.getElementById('assignDescription');
        if (descInput) {
            descInput.value = `S.Y. ${sy} - ${sem} Tuition`;
        }
    };

    window.toggleCategoryRow = function(catId) {
        const row = document.getElementById('catRow_' + catId);
        const chk = document.getElementById('chk_' + catId);
        const amt = document.getElementById('amt_' + catId);
        if (row && chk) {
            if (chk.checked) {
                row.classList.remove('excluded');
                amt.removeAttribute('disabled');
            } else {
                row.classList.add('excluded');
                amt.setAttribute('disabled', 'disabled');
            }
            recalcAssessmentTotal();
        }
    };

    function validateAssessmentAmount(input) {
        const value = String(input.value || '').trim();
        const valid = /^\d+(?:\.\d{1,2})?$/.test(value) && Number.isFinite(Number(value)) && Number(value) >= 0;
        input.classList.toggle('amount-invalid', value !== '' && !valid);
        input.setCustomValidity(value !== '' && !valid ? 'Enter a non-negative amount with no more than 2 decimal places.' : '');
        return valid;
    }

    document.querySelectorAll('.fee-category-row input[type="number"]').forEach(input => {
        input.addEventListener('keydown', event => {
            if (['-', '+', 'e', 'E'].includes(event.key)) event.preventDefault();
        });
        input.addEventListener('input', () => {
            validateAssessmentAmount(input);
            recalcAssessmentTotal();
        });
        validateAssessmentAmount(input);
    });

    window.recalcAssessmentTotal = function() {
        let total = 0;
        document.querySelectorAll('.fee-category-row').forEach(row => {
            const chk = row.querySelector('input[type="checkbox"]');
            const num = row.querySelector('input[type="number"]');
            if (chk && chk.checked && num) {
                if (validateAssessmentAmount(num)) total += Number(num.value || 0);
            }
        });
        const disp = document.getElementById('computedTotalDisplay');
        if (disp) {
            disp.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };

    // Initialize total calculation
    recalcAssessmentTotal();

    // ── Manual Payment Modal ──
    window.openManualPaymentModal = function() {
        document.getElementById('manualPaymentModal')?.classList.add('active');
    };
    document.getElementById('btnCloseManualPay')?.addEventListener('click', () => {
        document.getElementById('manualPaymentModal')?.classList.remove('active');
    });

    // ── HTML Escape Helper ──
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    window.escapeHtml = escapeHtml;

    // ── Category Modal ──
    window.openAddCategoryModal = function() {
        document.getElementById('catModalTitle').textContent = 'Request New Fee Category';
        const sub = document.getElementById('catModalSub');
        if (sub) sub.textContent = 'Submit a new fee aspect for Administrator review and approval.';
        document.getElementById('catModalId').value = '0';
        document.getElementById('catModalName').value = '';
        document.getElementById('catModalAmount').value = '';
        document.getElementById('catModalVariable').checked = false;
        document.getElementById('catModalSort').value = '10';
        const btn = document.getElementById('btnSubmitCategory');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span id="btnSubmitCategoryText">Request to Admin</span>';
        }
        document.getElementById('categoryModal')?.classList.add('active');
    };

    window.openEditCategoryModal = function(cat) {
        document.getElementById('catModalTitle').textContent = 'Edit Fee Category';
        const sub = document.getElementById('catModalSub');
        if (sub) sub.textContent = 'Update standard fee category details.';
        document.getElementById('catModalId').value = cat.id;
        document.getElementById('catModalName').value = cat.name;
        document.getElementById('catModalAmount').value = cat.default_amount;
        document.getElementById('catModalVariable').checked = cat.is_variable == 1;
        document.getElementById('catModalSort').value = cat.sort_order;
        const btn = document.getElementById('btnSubmitCategory');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span id="btnSubmitCategoryText">Save Changes</span>';
        }
        document.getElementById('categoryModal')?.classList.add('active');
    };

    // Category form submission with loading overlay to prevent spamming
    const categoryForm = document.getElementById('categoryForm');
    const btnSubmitCategory = document.getElementById('btnSubmitCategory');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            const nameVal = document.getElementById('catModalName')?.value.trim();
            if (!nameVal) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Category Name Required', text: 'Please enter a name for the fee category.' });
                return;
            }
            if (btnSubmitCategory && btnSubmitCategory.disabled) {
                e.preventDefault();
                return;
            }
            if (btnSubmitCategory) {
                btnSubmitCategory.disabled = true;
                btnSubmitCategory.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Sending...';
            }
            const isNew = document.getElementById('catModalId').value == '0';
            if (accountingLoadingTitle) accountingLoadingTitle.textContent = isNew ? 'Submitting Request to Admin...' : 'Saving Fee Category...';
            if (accountingLoadingDesc) accountingLoadingDesc.textContent = isNew ? 'Sending request notification to Administrator for approval. Please wait...' : 'Updating fee category records. Please wait...';
            if (accountingLoadingOverlay) {
                accountingLoadingOverlay.classList.add('active');
                accountingLoadingOverlay.style.display = 'flex';
            }
        });
    }

        // ── Official Assessment Receipt View Modal ──
    window.viewAssessmentReceipt = function(fee) {
        if (!fee) return;
        document.getElementById('arNumber').textContent = '#TF-' + fee.id;
        document.getElementById('arDate').textContent = fee.created_at ? new Date(fee.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '<?= date("M d, Y") ?>';
        document.getElementById('arStudentName').textContent = (fee.first_name || '') + ' ' + (fee.last_name || '');
        document.getElementById('arStudentNum').textContent = fee.student_num || '—';
        document.getElementById('arGradeLevel').textContent = fee.grade_level || 'BSCS 11A1';
        document.getElementById('arTerm').textContent = (fee.school_year || 'S.Y.') + ' (' + (fee.semester || '1st Semester') + ')';
        document.getElementById('arDescription').textContent = fee.description || 'Tuition Assessment';

        const totalAmount = parseFloat(fee.total_amount || 0);
        const amountPaid = parseFloat(fee.amount_paid || 0);
        const remaining = Math.max(0, totalAmount - amountPaid);

        document.getElementById('arTotalAssessed').textContent = '₱' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('arSumAssessed').textContent = '₱' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('arSumPaid').textContent = '₱' + amountPaid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('arSumRemaining').textContent = '₱' + remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const badge = document.getElementById('arStatusBadge');
        if (remaining <= 0 && totalAmount > 0) {
            badge.textContent = 'FULLY PAID';
            badge.style.background = '#dcfce7';
            badge.style.color = '#166534';
        } else if (amountPaid > 0) {
            badge.textContent = 'PARTIAL PAYMENT';
            badge.style.background = '#fef3c7';
            badge.style.color = '#92400e';
        } else {
            badge.textContent = 'UNPAID';
            badge.style.background = '#fee2e2';
            badge.style.color = '#991b1b';
        }

        // Render item breakdown
        const itemsBody = document.getElementById('arItemsBody');
        const items = Array.isArray(fee.items) ? fee.items : [];
        if (items.length > 0) {
            itemsBody.innerHTML = items.map(item => `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 7px 10px; color: #1e293b;">${escapeHtml(item.category_name || item.name || 'Tuition Aspect')}</td>
                    <td style="padding: 7px 10px; text-align: right; font-weight: 600; color: #0f172a;">₱${parseFloat(item.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        } else {
            itemsBody.innerHTML = `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 7px 10px; color: #1e293b;">Tuition Assessment Fee</td>
                    <td style="padding: 7px 10px; text-align: right; font-weight: 600; color: #0f172a;">₱${totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `;
        }

        // Render payment transactions
        const paymentsBody = document.getElementById('arPaymentsBody');
        const payments = Array.isArray(fee.payments) ? fee.payments : [];
        if (payments.length > 0) {
            paymentsBody.innerHTML = payments.map(p => `
                <tr style="border-bottom: 1px solid #f0fdf4;">
                    <td style="padding: 6px 10px; font-weight: 700; color: #0b3d2e;">${escapeHtml(p.or_number || 'OR-N/A')}</td>
                    <td style="padding: 6px 10px; color: #475569;">${p.paid_at ? new Date(p.paid_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : (p.created_at || '—')}</td>
                    <td style="padding: 6px 10px; text-transform: uppercase; font-weight: 600;">${escapeHtml(p.payment_method || 'CASH')}</td>
                    <td style="padding: 6px 10px; text-align: right; font-weight: 700; color: #059669;">₱${parseFloat(p.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        } else {
            paymentsBody.innerHTML = `
                <tr><td colspan="4" style="padding: 10px; text-align: center; color: #94a3b8; font-style: italic;">No official receipts recorded for this assessment.</td></tr>
            `;
        }

        document.getElementById('assessmentReceiptModal')?.classList.add('active');
    };

    window.showRejectionReason = function(name, reason) {
        const cleanName = escapeHtml(name || 'Fee Category');
        const cleanReason = escapeHtml(reason || 'No specific reason provided by administrator.');
        Swal.fire({
            icon: 'error',
            title: 'Fee Category Rejected',
            html: `
                <div style="text-align: left; font-size: 13.5px; color: #334155;">
                    <p style="margin-bottom: 12px;">The request for <strong>${cleanName}</strong> was rejected by the System Administrator.</p>
                    <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-left: 4px solid #ef4444; border-radius: 8px; padding: 14px; margin-top: 10px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #991b1b; letter-spacing: 0.5px; margin-bottom: 4px;">Rejection Reason:</div>
                        <div style="color: #7f1d1d; font-size: 13px; line-height: 1.5; font-weight: 500;">${cleanReason}</div>
                    </div>
                </div>
            `,
            confirmButtonColor: '#0b3d2e',
            confirmButtonText: 'Close'
        });
    };

    // ── Receipt Voucher Modal ──
    window.viewReceiptVoucher = function(p) {
        document.getElementById('vOrNumber').textContent = p.or_number;
        document.getElementById('vStudentName').textContent = p.first_name + ' ' + p.last_name;
        document.getElementById('vStudentNum').textContent = p.student_num;
        document.getElementById('vMethod').textContent = (p.payment_method || 'CASH').toUpperCase();
        document.getElementById('vDate').textContent = p.paid_at || p.created_at;
        document.getElementById('vAmount').textContent = '₱' + parseFloat(p.amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('receiptModal')?.classList.add('active');
    };


    // Notification dropdown
    const btnAccNotif = document.getElementById('btnAccNotif');
    const accNotifDropdown = document.getElementById('accNotifDropdown');
    btnAccNotif?.addEventListener('click', (e) => {
        e.stopPropagation();
        accNotifDropdown?.classList.toggle('open');
        btnAccNotif.classList.toggle('active');
    });
    document.addEventListener('click', () => {
        accNotifDropdown?.classList.remove('open');
        btnAccNotif?.classList.remove('active');
    });

    // Live search filter in accounting
    const accountingSearch = document.getElementById('accountingSearch');
    if (accountingSearch) {
        accountingSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Ctrl+K keyboard shortcut to focus search
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                accountingSearch.focus();
                accountingSearch.select();
            }
            if (e.key === 'Escape' && document.activeElement === accountingSearch) {
                accountingSearch.blur();
                accountingSearch.value = '';
                accountingSearch.dispatchEvent(new Event('input'));
            }
        });
    }

    // ── Assign Tuition Form – loading overlay on submit ──
    const assignTuitionForm = document.getElementById('assignTuitionForm');
    const btnSubmitAssignModal = document.getElementById('btnSubmitAssignModal');
    const accountingLoadingOverlay = document.getElementById('accountingLoadingOverlay');
    const accountingLoadingTitle = document.getElementById('accountingLoadingTitle');
    const accountingLoadingDesc = document.getElementById('accountingLoadingDesc');

    if (assignTuitionForm) {
        assignTuitionForm.addEventListener('submit', function(e) {
            const invalidAmount = Array.from(assignTuitionForm.querySelectorAll('.fee-category-row input[type="checkbox"]:checked'))
                .map(checkbox => checkbox.closest('.fee-category-row')?.querySelector('input[type="number"]'))
                .find(input => input && !validateAssessmentAmount(input));
            if (invalidAmount) {
                e.preventDefault();
                invalidAmount.focus();
                invalidAmount.reportValidity();
                return;
            }
            // Basic client-side check: at least one category checked
            const anyChecked = assignTuitionForm.querySelectorAll('input[type="checkbox"]:checked').length > 0;
            if (!anyChecked) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'No Fee Selected', text: 'Please select at least one fee category.' });
                return;
            }
            // Show loading overlay
            if (accountingLoadingTitle) accountingLoadingTitle.textContent = 'Sending Assessment…';
            if (accountingLoadingDesc) accountingLoadingDesc.textContent = 'Saving tuition data and sending email notification to student. Please wait.';
            if (accountingLoadingOverlay) accountingLoadingOverlay.style.display = 'flex';
            if (btnSubmitAssignModal) { btnSubmitAssignModal.disabled = true; btnSubmitAssignModal.textContent = 'Processing…'; }
        });
    }

    // ── Manual Payment Form – loading overlay on submit ──
    const manualPaymentForm = document.getElementById('manualPaymentForm');
    const btnSubmitManualPay = document.getElementById('btnSubmitManualPay');

    if (manualPaymentForm) {
        manualPaymentForm.addEventListener('submit', function(e) {
            const amtInput = manualPaymentForm.querySelector('#payAmount');
            const amt = parseFloat(amtInput ? amtInput.value : 0);
            if (!amt || amt <= 0) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid payment amount greater than 0.' });
                return;
            }
            if (accountingLoadingTitle) accountingLoadingTitle.textContent = 'Recording Payment…';
            if (accountingLoadingDesc) accountingLoadingDesc.textContent = 'Saving payment record and sending receipt email. Please wait.';
            if (accountingLoadingOverlay) accountingLoadingOverlay.style.display = 'flex';
            if (btnSubmitManualPay) { btnSubmitManualPay.disabled = true; btnSubmitManualPay.textContent = 'Processing…'; }
        });
    }

    // ── payFeeId: show balance hint + set max on amount input ──
    const payFeeIdSelect = document.getElementById('payFeeId');
    const payFeeBalanceHint = document.getElementById('payFeeBalanceHint');
    const payAmountInput = document.getElementById('payAmount');

    if (payFeeIdSelect) {
        payFeeIdSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const remaining = parseFloat(selected.dataset.balance || 0);
            const studentName = selected.dataset.student || '';

            if (payFeeBalanceHint) {
                if (this.value) {
                    payFeeBalanceHint.textContent = `Remaining balance for ${studentName}: ₱${remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    payFeeBalanceHint.style.display = 'block';
                } else {
                    payFeeBalanceHint.style.display = 'none';
                }
            }

            if (payAmountInput) {
                payAmountInput.value = '';
                if (this.value && remaining > 0) {
                    payAmountInput.max = remaining;
                    payAmountInput.removeAttribute('disabled');
                } else {
                    payAmountInput.removeAttribute('max');
                    payAmountInput.setAttribute('disabled', 'disabled');
                    if (this.value && remaining <= 0 && payFeeBalanceHint) {
                        payFeeBalanceHint.textContent = 'This fee is already fully paid.';
                    }
                }
            }
        });
    }

    // ── Negative / invalid character blocker on payAmount ──
    if (payAmountInput) {
        payAmountInput.addEventListener('keydown', function(e) {
            if (['-', '+', 'e', 'E'].includes(e.key)) e.preventDefault();
        });
        payAmountInput.addEventListener('input', function() {
            // Strip anything that slipped through (e.g. paste)
            this.value = this.value.replace(/[^0-9.]/g, '');
            // Remove extra decimal points
            const parts = this.value.split('.');
            if (parts.length > 2) this.value = parts[0] + '.' + parts.slice(1).join('');
            // Enforce max
            const max = parseFloat(this.max);
            if (!isNaN(max) && parseFloat(this.value) > max) this.value = max.toFixed(2);
        });
    }

        // ── Real-time polling via AJAX (every 2.5 seconds) ──
    let lastPaymentId = <?= !empty($payments) ? (int) max(array_map(static fn($payment) => (int)($payment['id'] ?? 0), $payments)) : 0 ?>;
    let lastKnownCategoriesHash = null;

    // Real-time table row updater for Fee Categories
    function renderCategoryRowHtml(cat) {
        const approval = cat.approval_status || 'approved';
        let badgeHtml = '';
        let actionHtml = '';

        if (approval === 'pending') {
            badgeHtml = `<span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 600; padding: 4px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;">
                <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true" style="width: 8px; height: 8px;"></span>
                Pending Admin Approval
            </span>`;
            actionHtml = `
                <button type="button" class="btn disabled" style="padding: 4px 8px; font-size: 11.5px; opacity: 0.65; cursor: not-allowed; background: #f1f5f9; color: #64748b;" disabled title="Awaiting Admin Review">
                    ⏳ Under Review
                </button>
                <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_${cat.id}">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_fee_category">
                    <input type="hidden" name="category_id" value="${cat.id}">
                    <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                        onclick="confirmDeleteCategory(${cat.id}, '${escapeHtml(cat.name)}', 'cancel')">Cancel Request</button>
                </form>
            `;
        } else if (approval === 'rejected') {
            badgeHtml = `<span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 600; padding: 4px 8px; border-radius: 4px; cursor: pointer;"
                data-name="${escapeHtml(cat.name)}"
                data-reason="${escapeHtml(cat.rejection_reason || '')}"
                onclick="showRejectionReason(this.dataset.name, this.dataset.reason)"
                title="Click to view rejection reason">
                ✕ Rejected
            </span>`;
            actionHtml = `
                <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #991b1b; border-color: #fecaca; background: #fff1f2; font-weight: 600;"
                    data-name="${escapeHtml(cat.name)}"
                    data-reason="${escapeHtml(cat.rejection_reason || '')}"
                    onclick="showRejectionReason(this.dataset.name, this.dataset.reason)">
                    Reason
                </button>
                <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_${cat.id}">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_fee_category">
                    <input type="hidden" name="category_id" value="${cat.id}">
                    <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                        onclick="confirmDeleteCategory(${cat.id}, '${escapeHtml(cat.name)}')">Delete</button>
                </form>
            `;
        } else {
            badgeHtml = `<span class="badge" style="background: #dcfce7; color: #166534; font-weight: 600; padding: 4px 8px; border-radius: 4px;">
                ✓ Approved &amp; Active
            </span>`;
            actionHtml = `
                <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px;" onclick='openEditCategoryModal(${JSON.stringify(cat)})'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/public/accounting/?view=categories" style="display: inline;" id="deleteCatForm_${cat.id}">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_fee_category">
                    <input type="hidden" name="category_id" value="${cat.id}">
                    <button type="button" class="btn" style="padding: 4px 8px; font-size: 11.5px; color: #ef4444; border-color: #fee2e2; background: #fff5f5;"
                        onclick="confirmDeleteCategory(${cat.id}, '${escapeHtml(cat.name)}')">Delete</button>
                </form>
            `;
        }

        const typeBadge = cat.is_variable == 1
            ? '<span class="badge info">Variable Remainder</span>'
            : '<span class="badge success">Fixed Aspect</span>';

        return `
            <tr data-cat-id="${cat.id}">
                <td><code>${escapeHtml(cat.code)}</code></td>
                <td><strong>${escapeHtml(cat.name)}</strong></td>
                <td><strong style="color: #0b3d2e;">${cat.formatted_amount || ('₱' + parseFloat(cat.default_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }))}</strong></td>
                <td>${typeBadge}</td>
                <td>${badgeHtml}</td>
                <td style="text-align: right;">${actionHtml}</td>
            </tr>
        `;
    }

    function pollRealtimeAjax() {
        $.ajax({
            url: '<?= APP_URL ?>/public/accounting/?action=realtime_feed&last_payment_id=' + lastPaymentId + '&_t=' + Date.now(),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(data) {
                if (!data || !data.success) return;

                // 1. Live Categories Sync (when Admin approves or rejects)
                if (data.categories_hash) {
                    if (lastKnownCategoriesHash !== null && lastKnownCategoriesHash !== data.categories_hash) {
                        const tbody = document.getElementById('categoryTableBody');
                        if (tbody && Array.isArray(data.categories)) {
                            tbody.innerHTML = data.categories.map(renderCategoryRowHtml).join('');
                            if (window.Swal) {
                                Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 4500,
                                    timerProgressBar: true
                                }).fire({
                                    icon: 'info',
                                    title: 'Fee Category updated in real-time!'
                                });
                            }
                        }
                    }
                    lastKnownCategoriesHash = data.categories_hash;
                }

                // 2. Live Notifications Count & Badge
                const notifButton = document.getElementById('btnAccNotif');
                if (data.pending_fee_count > 0 || (data.new_payments && data.new_payments.length > 0)) {
                    if (notifButton && !notifButton.querySelector('.notif-dot-red')) {
                        const dot = document.createElement('span');
                        dot.className = 'notif-dot-red';
                        notifButton.appendChild(dot);
                    }
                }

                // 3. Live Payment Toasts
                if (data.new_payments && data.new_payments.length > 0) {
                    data.new_payments.forEach(p => {
                        lastPaymentId = Math.max(lastPaymentId, p.id);
                        if (window.Swal) {
                            Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            }).fire({
                                icon: 'success',
                                title: `New payment: ₱${parseFloat(p.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })} from ${p.first_name} ${p.last_name}`
                            });
                        }
                    });
                }

                // 4. Update KPI & Assessment Rows
                if (data.metrics) {
                    const rev = document.getElementById('kpiTotalRevenue');
                    const rec = document.getElementById('kpiTotalReceivables');
                    const col = document.getElementById('kpiCollectionRate');
                    if (rev && data.metrics.formatted_revenue) rev.textContent = data.metrics.formatted_revenue;
                    if (rec && data.metrics.formatted_receivables) rec.textContent = data.metrics.formatted_receivables;
                    if (col && data.metrics.formatted_collection_rate) col.textContent = data.metrics.formatted_collection_rate;
                }

                if (data.students && Array.isArray(data.students)) {
                    data.students.forEach(s => {
                        const cell = document.querySelector(`tr[data-student-id="${s.id}"] .student-balance-cell`);
                        if (cell && s.formatted_balance) cell.textContent = s.formatted_balance;
                    });
                }

                if (data.assessments) {
                    Object.entries(data.assessments).forEach(([feeId, assessment]) => {
                        const row = document.querySelector(`tr[data-fee-id="${feeId}"]`);
                        if (!row) return;
                        const total = row.querySelector('.assessment-total');
                        const paid = row.querySelector('.assessment-paid');
                        const remaining = row.querySelector('.assessment-remaining');
                        const status = row.querySelector('.assessment-status');
                        if (total) total.textContent = assessment.formatted_total;
                        if (paid) paid.textContent = assessment.formatted_paid;
                        if (remaining) {
                            remaining.textContent = assessment.formatted_remaining;
                            remaining.style.color = assessment.remaining_balance > 0 ? '#b45309' : '#166534';
                        }
                        if (status) {
                            const badge = status.querySelector('.badge');
                            if (badge) {
                                badge.className = `badge ${assessment.status === 'paid' ? 'success' : assessment.status === 'partial' ? 'warning' : 'danger'}`;
                                badge.textContent = assessment.status.charAt(0).toUpperCase() + assessment.status.slice(1);
                            }
                        }
                    });
                }
            }
        });
    }

    // ── Refund Modals Logic ──
    const REFUND_RECORDS = <?= json_encode(array_column($refunds ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const approveRefundModal = document.getElementById('approveRefundModal');
    const rejectRefundModal = document.getElementById('rejectRefundModal');
    const viewRefundModal = document.getElementById('viewRefundModal');

    function getRefundDataById(refOrId) {
        if (typeof refOrId === 'object' && refOrId !== null) return refOrId;
        if (REFUND_RECORDS && REFUND_RECORDS[refOrId]) return REFUND_RECORDS[refOrId];
        const row = document.querySelector(`tr[data-refund-id="${refOrId}"]`);
        if (row && row.getAttribute('data-refund')) {
            try { return JSON.parse(row.getAttribute('data-refund')); } catch(e){}
        }
        return null;
    }

    window.openApproveRefundModal = function(refOrId) {
        const ref = getRefundDataById(refOrId);
        if (!ref || !approveRefundModal) {
            console.error('Approve refund record not found for:', refOrId);
            return;
        }
        document.getElementById('approveRefundId').value = ref.id;
        document.getElementById('approveStudentName').textContent = `${ref.first_name} ${ref.last_name}`;
        document.getElementById('approveStudentNum').textContent = ref.student_num || '';
        document.getElementById('approveRefundNum').textContent = ref.refund_number || '';
        document.getElementById('approveFeeDesc').textContent = ref.fee_desc || 'Tuition Assessment';
        document.getElementById('approveRefundAmount').textContent = '₱' + parseFloat(ref.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
        document.getElementById('approveStudentReason').textContent = `"${ref.reason}"`;
        document.getElementById('approveStudentEmail').textContent = ref.email || 'N/A';
        document.getElementById('approveParentEmail').textContent = ref.parent_email || 'None on file';
        document.getElementById('approveNotes').value = '';

        approveRefundModal.style.display = 'flex';
        approveRefundModal.classList.add('active', 'show');
        document.body.style.overflow = 'hidden';
    };

    window.closeApproveRefundModal = function() {
        if (!approveRefundModal) return;
        approveRefundModal.style.display = 'none';
        approveRefundModal.classList.remove('active', 'show');
        document.body.style.overflow = '';
    };

    window.openRejectRefundModal = function(refOrId) {
        const ref = getRefundDataById(refOrId);
        if (!ref || !rejectRefundModal) {
            console.error('Reject refund record not found for:', refOrId);
            return;
        }
        document.getElementById('rejectRefundId').value = ref.id;
        document.getElementById('rejectStudentName').textContent = `${ref.first_name} ${ref.last_name}`;
        document.getElementById('rejectRefundNum').textContent = ref.refund_number || '';
        document.getElementById('rejectRefundAmount').textContent = '₱' + parseFloat(ref.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
        document.getElementById('rejectionReasonInput').value = '';

        rejectRefundModal.style.display = 'flex';
        rejectRefundModal.classList.add('active', 'show');
        document.body.style.overflow = 'hidden';
    };

    window.closeRejectRefundModal = function() {
        if (!rejectRefundModal) return;
        rejectRefundModal.style.display = 'none';
        rejectRefundModal.classList.remove('active', 'show');
        document.body.style.overflow = '';
    };

    window.openViewRefundModal = function(refOrId) {
        const ref = getRefundDataById(refOrId);
        if (!ref || !viewRefundModal) return;
        document.getElementById('viewRefundSub').textContent = `Reference: ${ref.refund_number}`;
        document.getElementById('viewStudentName').textContent = `${ref.first_name} ${ref.last_name}`;
        document.getElementById('viewStudentNum').textContent = ref.student_num || '';
        document.getElementById('viewFeeDesc').textContent = ref.fee_desc || '';
        document.getElementById('viewORNum').textContent = ref.or_number ? `OR# ${ref.or_number}` : 'Overall assessment credit';
        document.getElementById('viewRefundAmount').textContent = '₱' + parseFloat(ref.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
        document.getElementById('viewStudentReason').textContent = `"${ref.reason}"`;

        const resBox = document.getElementById('viewResolutionBox');
        if (ref.status === 'approved') {
            resBox.style.background = '#ecfdf5';
            resBox.style.border = '1px solid #a7f3d0';
            resBox.style.color = '#065f46';
            resBox.innerHTML = `
                <strong style="color:#047857;">✓ Status: Approved & Deducted</strong>
                <div style="margin-top:4px;font-size:12px;"><strong>Accounting Remarks:</strong> ${escapeHtml(ref.review_notes || 'Approved')}</div>
                <div style="font-size:11px;color:#059669;margin-top:2px;">Reviewed by: ${escapeHtml(ref.reviewer_name || 'Accounting Staff')} on ${ref.reviewed_at ? new Date(ref.reviewed_at).toLocaleString() : ''}</div>
            `;
        } else if (ref.status === 'rejected') {
            resBox.style.background = '#fef2f2';
            resBox.style.border = '1px solid #fecaca';
            resBox.style.color = '#991b1b';
            resBox.innerHTML = `
                <strong style="color:#b91c1c;">✕ Status: Declined</strong>
                <div style="margin-top:4px;font-size:12px;"><strong>Reason:</strong> ${escapeHtml(ref.rejection_reason || 'Declined')}</div>
                <div style="font-size:11px;color:#dc2626;margin-top:2px;">Reviewed by: ${escapeHtml(ref.reviewer_name || 'Accounting Staff')} on ${ref.reviewed_at ? new Date(ref.reviewed_at).toLocaleString() : ''}</div>
            `;
        } else {
            resBox.style.background = '#fefce8';
            resBox.style.border = '1px solid #fef08a';
            resBox.style.color = '#854d0e';
            resBox.innerHTML = `<strong>⏳ Status: Pending Review</strong><div style="margin-top:2px;font-size:12px;">Submitted on ${new Date(ref.requested_at).toLocaleString()}</div>`;
        }

        viewRefundModal.style.display = 'flex';
        viewRefundModal.classList.add('active', 'show');
        document.body.style.overflow = 'hidden';
    };

    window.closeViewRefundModal = function() {
        if (!viewRefundModal) return;
        viewRefundModal.style.display = 'none';
        viewRefundModal.classList.remove('active', 'show');
        document.body.style.overflow = '';
    };

    // Filter table
    window.filterRefundTable = function(status, btn) {
        document.querySelectorAll('#refundFilterTabs .btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = '';
            b.style.color = '';
        });
        if (btn) {
            btn.classList.add('active');
            btn.style.background = '#0b3d2e';
            btn.style.color = '#ffffff';
        }

        const rows = document.querySelectorAll('#accountingRefundsTable .refund-row');
        rows.forEach(r => {
            const rowStatus = r.getAttribute('data-status');
            if (status === 'all' || rowStatus === status) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    };

    // Search filter
    document.getElementById('refundSearchInput')?.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('#accountingRefundsTable .refund-row').forEach(row => {
            const data = row.getAttribute('data-search') || '';
            row.style.display = data.includes(query) ? '' : 'none';
        });
    });

    // Confirmation before submitting approve form
    document.getElementById('approveRefundForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const studentName = document.getElementById('approveStudentName')?.textContent || 'Student';
        const amount = document.getElementById('approveRefundAmount')?.textContent || '';
        Swal.fire({
            title: 'Approve & Adjust Balance?',
            html: `Are you sure you want to approve this refund of <strong>${amount}</strong> for <strong>${studentName}</strong>?<br><br><small style="color:#64748b;">This will deduct from their paid tuition, update net revenue, and automatically send notification emails to the student and parent.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve & Send Email',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#94a3b8'
        }).then(result => {
            if (result.isConfirmed) {
                closeApproveRefundModal();
                const overlay = document.getElementById('accountingLoadingOverlay');
                if (overlay) {
                    const title = document.getElementById('accountingLoadingTitle');
                    const desc = document.getElementById('accountingLoadingDesc');
                    if (title) title.textContent = 'Approving Refund & Updating Ledger...';
                    if (desc) desc.textContent = 'Please wait while student balance is adjusted and confirmation emails are dispatched to the student and parent.';
                    overlay.classList.add('active');
                }
                document.getElementById('approveRefundForm').submit();
            }
        });
    });

    // Confirmation before submitting reject form
    document.getElementById('rejectRefundForm')?.addEventListener('submit', function(e) {
        const reason = (document.getElementById('rejectionReasonInput')?.value || '').trim();
        if (reason.length < 5) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Explanation Required',
                text: 'Please provide at least 5 characters explaining why the refund is being declined.'
            });
            return;
        }

        e.preventDefault();
        Swal.fire({
            title: 'Reject Refund Request?',
            text: 'An email notice containing your rejection reason will be sent to the student and parent.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Decline Request',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8'
        }).then(result => {
            if (result.isConfirmed) {
                closeRejectRefundModal();
                const overlay = document.getElementById('accountingLoadingOverlay');
                if (overlay) {
                    const title = document.getElementById('accountingLoadingTitle');
                    const desc = document.getElementById('accountingLoadingDesc');
                    if (title) title.textContent = 'Declining Refund Request...';
                    if (desc) desc.textContent = 'Please wait while the update is recorded and explanation notice is emailed to the student and parent.';
                    overlay.classList.add('active');
                }
                document.getElementById('rejectRefundForm').submit();
            }
        });
    });

    // Start AJAX polling every 2.5 seconds
    setInterval(pollRealtimeAjax, 2500);
    window.addEventListener('focus', pollRealtimeAjax);
</script>

<!-- Bootstrap 5.3 JS (CDN with Subresource Integrity & Local Fallback) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= APP_URL ?>/assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
