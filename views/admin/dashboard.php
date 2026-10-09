<?php
/**
 * PayTrack — Administrator Portal
 * User Governance, Online Tracking & Days Online, Multi-Recipient Student Enrollment,
 * Accounting Staff Creation, User Deletion & Status Management, System Email Logs.
 */
$adminUsername = Auth::username() ?? 'admin';
$studentValue = static fn(string $key, string $default = ''): string => e((string) ($studentFormData[$key] ?? $default));
$studentError = static fn(string $key): string => !empty($studentFormErrors[$key]) ? ' input-invalid' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>PayTrack — System Administrator</title>
    <!-- Bootstrap 5.3 (Local & CDN with Subresource Integrity) -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/bootstrap/css/bootstrap.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- jQuery 3.7.1 for AJAX Operations -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal-chrome.css?v=<?= filemtime(__DIR__ . '/../../assets/css/portal-chrome.css') ?>">
    <style>
        .online-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .online-dot.green {
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.35);
            animation: pulse-online 2s infinite;
        }
        .online-dot.gray {
            background: #94a3b8;
        }
        @keyframes pulse-online {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }
        .role-pill-admin {
            background: #0f172a;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .role-pill-accounting {
            background: #0b3d2e;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .role-pill-student {
            background: #0284c7;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .filter-tab-btn {
            background: none;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-tab-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .filter-tab-btn.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
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
            <div class="org-icon">A</div>
            <div class="org-text">
                <div class="org-name">PayTrack</div>
                <div class="org-team">Admin Portal</div>
            </div>
            <button class="mobile-menu-btn" id="btnCloseSidebar" style="margin-left: auto; width: 28px; height: 28px; font-size: 14px;">&times;</button>
        </div>

        <ul class="nav">
            <li>
                <a href="<?= APP_URL ?>/public/admin/?view=home" class="nav-item <?= $currentView === 'home' ? 'active' : '' ?>">
                    <span class="ic">&#8962;</span> Home
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/admin/?view=users" class="nav-item <?= $currentView === 'users' ? 'active' : '' ?>">
                    <span class="ic">&#128101;</span> User Governance
                    <span class="badge-count"><?= $totalUsers ?></span>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/admin/?view=fee_approvals" class="nav-item <?= $currentView === 'fee_approvals' ? 'active' : '' ?>">
                    <span class="ic">&#9881;</span> Fee Approvals
                    <?php if (!empty($pendingFeeCount) && $pendingFeeCount > 0): ?>
                        <span class="badge-count" style="background: #dc2626; color: #fff; font-weight: 800;"><?= $pendingFeeCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/admin/?view=logs" class="nav-item <?= $currentView === 'logs' ? 'active' : '' ?>">
                    <span class="ic">&#9993;</span> System Email Logs
                    <span class="badge-count"><?= $totalLogsCount ?></span>
                </a>
            </li>
        </ul>

        <div style="margin-top: auto; padding-top: 18px; border-top: 1px solid #e2e8f0;">
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
                <input type="text" id="adminUserSearch" placeholder="Search user by name, ID, username, or email..." autocomplete="off" value="<?= e($_GET['search'] ?? '') ?>">
                <span class="kbd-badge">Ctrl + K</span>
            </div>

            <div class="topbar-actions" style="display: flex; align-items: center; gap: 12px;">
                <?php
                $adminNotifs = [];

                // Display each pending fee category request from Accounting
                if (!empty($pendingFeeCategories)) {
                    foreach ($pendingFeeCategories as $pReq) {
                        $adminNotifs[] = [
                            'type' => 'warning',
                            'icon' => '⏳',
                            'title' => 'New Fee Request: ' . $pReq['name'],
                            'desc' => peso($pReq['default_amount']) . ' · Requested by ' . ($pReq['requester_name'] ?: 'Accounting Office'),
                            'time' => date('M d, h:i A', strtotime($pReq['requested_at'] ?? 'now')),
                            'link' => APP_URL . '/public/admin/?view=fee_approvals',
                            'unread' => true,
                        ];
                    }
                }
                if ($onlineCount > 0) {
                    $adminNotifs[] = [
                        'type' => 'success',
                        'icon' => '🟢',
                        'title' => $onlineCount . ' user(s) currently online',
                        'desc' => 'Live session tracking is active across student, accounting, and admin portals.',
                        'time' => 'Live Status',
                        'link' => APP_URL . '/public/admin/?view=users',
                        'unread' => true,
                    ];
                }
                $adminNotifs[] = [
                    'type' => 'info',
                    'icon' => '👥',
                    'title' => $totalUsers . ' registered accounts',
                    'desc' => $studentCount . ' students · ' . $accountingCount . ' accounting · ' . $adminCount . ' admin',
                    'time' => 'Directory Snapshot',
                    'link' => APP_URL . '/public/admin/?view=users',
                    'unread' => false,
                ];
                if ($totalLogsCount > 0) {
                    $adminNotifs[] = [
                        'type' => 'warning',
                        'icon' => '✉️',
                        'title' => 'Email audit trail available',
                        'desc' => $totalLogsCount . ' recent system notices and credential emails logged.',
                        'time' => 'System Logs',
                        'link' => APP_URL . '/public/admin/?view=logs',
                        'unread' => false,
                    ];
                }
                $adminUnread = 0;
                foreach ($adminNotifs as $n) {
                    if (!empty($n['unread'])) $adminUnread++;
                }
                ?>
                <div class="notif-wrapper">
                    <button type="button" class="notif-bell-btn" id="btnAdminNotif" aria-label="Notifications" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($adminUnread > 0): ?>
                            <span class="notif-dot-red"></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-dropdown-menu" id="adminNotifDropdown" role="region" aria-label="Notifications">
                        <div class="notif-header">
                            <div class="notif-header-title">
                                <span>🔔 Notifications</span>
                                <span class="notif-header-badge"><?= $adminUnread ?> new</span>
                            </div>
                        </div>
                        <ul class="notif-list-body">
                            <?php foreach ($adminNotifs as $notif): ?>
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
                            <a href="<?= APP_URL ?>/public/admin/?view=logs">View System Email Logs &rarr;</a>
                        </div>
                    </div>
                </div>

                <div class="user-chip-container">
                    <div class="user-chip-meta">
                        <span class="user-chip-name">Administrator</span>
                        <span class="user-chip-id">(<?= e($adminUsername) ?>)</span>
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
            <!-- VIEW 0: ADMIN HOME / OVERVIEW                 -->
            <!-- ============================================== -->
            <?php if ($currentView === 'home'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #e0f2fe; color: #0284c7;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Welcome back, Administrator!</h1>
                            <p class="portal-page-sub">
                                Signed in as <strong><?= e($adminUsername) ?></strong> &bull;
                                <?= date('M d, Y') ?> &bull;
                                System governance overview
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn" style="background: #1d4ed8; color: #fff; font-weight: 700; padding: 10px 18px;" onclick="openEnrollStudentModal()">
                        + Enroll Student
                    </button>
                </div>

                <div class="metric-summary-grid">
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Total Registered Users</div>
                        <div class="metric-summary-value" style="color: #1e3a8a;"><?= $totalUsers ?></div>
                        <div class="metric-summary-sub"><?= $studentCount ?> students · <?= $accountingCount ?> accounting · <?= $adminCount ?> admin</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Currently Online</div>
                        <div class="metric-summary-value" style="color: #059669;"><?= $onlineCount ?></div>
                        <div class="metric-summary-sub"><?= $totalUsers > 0 ? round(($onlineCount / $totalUsers) * 100) : 0 ?>% of total users</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Active This Week</div>
                        <div class="metric-summary-value"><?= $activeWeekCount ?></div>
                        <div class="metric-summary-sub">Logged in within the last 7 days</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">System Email Logs</div>
                        <div class="metric-summary-value" style="color: #ea580c;"><?= $totalLogsCount ?></div>
                        <div class="metric-summary-sub"><a href="<?= APP_URL ?>/public/admin/?view=logs" style="color: #2563eb; font-weight: 600; text-decoration: none;">View audit trail &rarr;</a></div>
                    </div>
                </div>

                <div class="dashboard-2col-grid">
                    <div class="section-box" style="margin-bottom: 0;">
                        <div class="section-box-header">
                            <div class="section-header-left">
                                <div class="section-header-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <h2 class="section-box-title">Recent Accounts</h2>
                            </div>
                            <a href="<?= APP_URL ?>/public/admin/?view=users" style="font-size: 12px; font-weight: 600; color: #2563eb; text-decoration: none;">Manage Users &rarr;</a>
                        </div>
                        <div class="table-responsive">
                            <table class="styled-fintech-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentUsers = array_slice($allUsers, 0, 5);
                                    if (empty($recentUsers)):
                                    ?>
                                        <tr><td colspan="4" style="text-align:center; color:#64748b; padding:28px;">No users registered yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentUsers as $ru): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($ru['display_name']) ?></strong>
                                                    <div style="font-size:11.5px;color:#64748b;"><?= e($ru['username']) ?></div>
                                                </td>
                                                <td>
                                                    <?php if ($ru['role'] === 'admin'): ?>
                                                        <span class="role-pill-admin">Admin</span>
                                                    <?php elseif ($ru['role'] === 'accounting'): ?>
                                                        <span class="role-pill-accounting">Accounting</span>
                                                    <?php else: ?>
                                                        <span class="role-pill-student">Student</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($ru['status'] === 'active'): ?>
                                                        <span class="status-badge-pill active"><span class="dot"></span> Active</span>
                                                    <?php else: ?>
                                                        <span class="status-badge-pill inactive"><span class="dot"></span> <?= e(ucfirst($ru['status'])) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($ru['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="portal-shortcuts-card">
                            <h4>Admin Portal Shortcuts</h4>
                            <div class="portal-shortcut-list">
                                <a href="<?= APP_URL ?>/public/admin/?view=users" class="btn"><span>👥</span> User Governance &amp; Status</a>
                                <a href="<?= APP_URL ?>/public/admin/?view=logs" class="btn"><span>✉️</span> System Email Audit Logs</a>
                                <button type="button" class="btn" onclick="openEnrollStudentModal()"><span>🎓</span> Enroll Student Account</button>
                                <button type="button" class="btn" onclick="openCreateAccountingModal()"><span>💼</span> Create Accounting Staff</button>
                            </div>
                        </div>
                        <div class="portal-advisory-card">
                            <div class="portal-advisory-title"><span>📢</span> Admin Advisory</div>
                            <p>Account status, roles, and online tracking update automatically. Use User Governance to enroll students, create accounting staff, and manage access.</p>
                        </div>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 1: USER GOVERNANCE & STATUS TRACKING     -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'users'): ?>
                
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #eff6ff; color: #2563eb;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">User Account Governance</h1>
                            <p class="portal-page-sub">Monitor users, track online status, manage accounts, and enroll students &amp; staff.</p>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="button" class="btn-primary-blue" onclick="openEnrollStudentModal()">+ Enroll Student</button>
                        <button type="button" class="btn-dark-navy" onclick="openCreateAccountingModal()">+ Create Accounting</button>
                    </div>
                </div>

                <div class="metric-summary-grid">
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Total Users</div>
                        <div class="metric-summary-value" style="color:#1e3a8a;"><?= $totalUsers ?></div>
                        <div class="metric-summary-sub"><?= $studentCount ?> Students · <?= $accountingCount ?> Accounting · <?= $adminCount ?> Admin</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Currently Online</div>
                        <div class="metric-summary-value" style="color:#059669;"><?= $onlineCount ?></div>
                        <div class="metric-summary-sub"><?= $totalUsers > 0 ? round(($onlineCount / $totalUsers) * 100) : 0 ?>% of total users</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">Active This Week</div>
                        <div class="metric-summary-value"><?= $activeWeekCount ?></div>
                        <div class="metric-summary-sub">Logged in within 7 days</div>
                    </div>
                    <div class="metric-summary-card">
                        <div class="metric-summary-label">System Email Logs</div>
                        <div class="metric-summary-value" style="color:#9a3412;"><?= $totalLogsCount ?></div>
                        <div class="metric-summary-sub">Notices &amp; receipts sent</div>
                    </div>
                </div>

                <!-- Guide UI: Table Card Box with Header Controls & Filters -->
                <div class="card-box" style="padding: 22px 24px;">
                    
                    <!-- Table Top Action & Filter Controls Bar -->
                    <div class="table-top-controls-row">
                        <div class="table-top-title-col">
                            <div class="table-icon-badge">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="table-top-heading">All Accounts (<span id="countDisplay"><?= $totalUsers ?></span>)</h2>
                                <p class="table-top-subtext">View and manage all registered accounts in the system.</p>
                            </div>
                        </div>

                        <div class="table-top-actions-col">
                            <!-- Inline Search -->
                            <div class="table-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="inlineAccountSearch" placeholder="Search in accounts...">
                            </div>

                            <!-- Role Filter Dropdown -->
                            <select id="roleDropdownFilter" class="select-filter-control" onchange="filterByDropdowns()">
                                <option value="all">All Roles</option>
                                <option value="student">Student</option>
                                <option value="accounting">Accounting</option>
                                <option value="admin">Admin</option>
                            </select>

                            <!-- Status Filter Dropdown -->
                            <select id="statusDropdownFilter" class="select-filter-control" onchange="filterByDropdowns()">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="inactive">Inactive</option>
                            </select>

                            <!-- Action Buttons -->
                            <button type="button" class="btn-primary-blue" onclick="openEnrollStudentModal()">
                                + Enroll Student Account
                            </button>
                            <button type="button" class="btn-dark-navy" onclick="openCreateAccountingModal()">
                                + Create Accounting Staff
                            </button>
                        </div>
                    </div>

                    <!-- Pills & Live Status Row -->
                    <div class="pills-and-legend-row">
                        <div class="filter-pills-group">
                            <button type="button" class="filter-tab-pill active" onclick="filterByTabPill('all', this)">All Accounts (<?= $totalUsers ?>)</button>
                            <button type="button" class="filter-tab-pill" onclick="filterByTabPill('student', this)">Students (<?= $studentCount ?>)</button>
                            <button type="button" class="filter-tab-pill" onclick="filterByTabPill('accounting', this)">Accounting (<?= $accountingCount ?>)</button>
                            <button type="button" class="filter-tab-pill" onclick="filterByTabPill('admin', this)">Admins (<?= $adminCount ?>)</button>
                        </div>

                        <div class="live-status-legend">
                            <span class="live-dot-pulse-green"></span>
                            <span>Showing live activity &amp; days online status</span>
                        </div>
                    </div>

                    <!-- Styled Fintech Table -->
                    <div class="table-responsive">
                        <table class="styled-fintech-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th style="width: 48px;">#</th>
                                    <th>User / Account</th>
                                    <th>Role</th>
                                    <th>Account Status</th>
                                    <th>Online Activity &amp; Days Online</th>
                                    <th>Date Registered</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rowIdx = 1;
                                $avatarColors = ['#818cf8', '#38bdf8', '#34d399', '#f472b6', '#fbbf24', '#a78bfa'];
                                foreach ($allUsers as $u): 
                                    $initial = strtoupper(substr($u['display_name'], 0, 1));
                                    $color = $avatarColors[abs(crc32($u['username'])) % count($avatarColors)];
                                ?>
                                    <tr data-role="<?= e($u['role']) ?>" data-status="<?= e($u['status']) ?>">
                                        <td style="color: #64748b; font-weight: 700; font-size: 12px;"><?= $rowIdx++ ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $color ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13.5px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                                    <?= $initial ?>
                                                </div>
                                                <div>
                                                    <strong style="color: #0f172a; font-size: 13.5px; display: block;"><?= e($u['display_name']) ?></strong>
                                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;">
                                                        Username: <code><?= e($u['username']) ?></code>
                                                        <?php if (!empty($u['contact_email']) && $u['contact_email'] !== '—'): ?>
                                                            &bull; <?= e($u['contact_email']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="role-pill-admin">Admin</span>
                                            <?php elseif ($u['role'] === 'accounting'): ?>
                                                <span class="role-pill-accounting">Accounting</span>
                                            <?php else: ?>
                                                <span class="role-pill-student">Student</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <span class="status-badge-pill active"><span class="dot"></span> Active</span>
                                            <?php elseif ($u['status'] === 'suspended'): ?>
                                                <span class="status-badge-pill suspended"><span class="dot"></span> Suspended</span>
                                            <?php else: ?>
                                                <span class="status-badge-pill inactive"><span class="dot"></span> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span class="online-dot <?= !empty($u['is_online']) ? 'green' : 'gray' ?>"></span>
                                                <strong style="color: <?= !empty($u['is_online']) ? '#16a34a' : '#475569' ?>;">
                                                    <?= !empty($u['is_online']) ? 'Online Now' : 'Offline' ?>
                                                </strong>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                                <?= e($u['days_online_text']) ?>
                                                <?php if (!empty($u['last_active_at'])): ?>
                                                    <span style="color: #94a3b8;">(<?= date('M d, Y h:i A', strtotime($u['last_active_at'])) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <span style="font-size: 12.5px; font-weight: 600; color: #0f172a;"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                            </div>
                                            <div style="font-size: 11px; color: #94a3b8; padding-left: 19px;"><?= date('h:i A', strtotime($u['created_at'])) ?></div>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                <!-- Status Toggle -->
                                                <?php if ($u['id'] !== Auth::userId()): ?>
                                                    <form method="POST" action="<?= APP_URL ?>/public/admin/?view=users" style="display: inline;" id="statusForm_<?= $u['id'] ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                        <button type="button" class="action-btn <?= $u['status'] === 'active' ? 'warning' : '' ?>"
                                                            title="<?= $u['status'] === 'active' ? 'Suspend User' : 'Activate User' ?>"
                                                            onclick="confirmStatusToggle(<?= $u['id'] ?>, '<?= htmlspecialchars($u['display_name'], ENT_QUOTES) ?>', '<?= $u['status'] ?>')">
                                                            <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                        </button>
                                                    </form>

                                                    <!-- Delete User -->
                                                    <button type="button" 
                                                            class="action-btn danger" 
                                                            title="Permanently Delete Account"
                                                            onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['display_name'], ENT_QUOTES) ?>', '<?= e($u['username']) ?>')">
                                                        Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span style="font-size: 11px; color: #94a3b8; font-style: italic; padding: 4px 8px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">Current Account</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Guide UI Pagination Controls -->
                    <div class="table-pagination-row">
                        <div class="show-entries-select">
                            <span>Show</span>
                            <select id="entriesPerPage">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span>entries</span>
                        </div>
                        <div class="pagination-controls">
                            <button type="button" class="pag-btn" disabled>&lt;</button>
                            <button type="button" class="pag-btn active">1</button>
                            <button type="button" class="pag-btn" disabled>&gt;</button>
                        </div>
                    </div>
                </div>

                <!-- Guide UI: Notice Callout Banner at bottom -->
                <div class="notice-callout-banner">
                    <div class="notice-callout-left">
                        <div class="notice-callout-icon">💡</div>
                        <div>
                            <strong style="color: #1e3a8a;">Notice:</strong>
                            <span style="color: #475569;">
                                User account status, roles, and online tracking update automatically. System timestamps reflect Philippine Standard Time (PST). Active sessions refresh periodically to safeguard account governance.
                            </span>
                        </div>
                    </div>
                    <button type="button" class="notice-callout-btn" onclick="openEnrollStudentModal()">
                        + Enroll Student
                    </button>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 2: SYSTEM EMAIL AUDIT LOGS               -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'logs'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #f1f5f9; color: #334155;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">System-Wide Email Audit Logs</h1>
                            <p class="portal-page-sub">Trace, filter, and inspect automated emails sent to students, parents, and accounting staff.</p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filter & Search Toolbar -->
                <div class="section-box" style="margin-bottom: 20px; padding: 18px 22px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1; min-width: 280px;">
                            <!-- Search Bar -->
                            <div class="search-container" style="flex: 1; min-width: 240px; max-width: 380px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="logSearchInput" placeholder="Search recipient email, subject, or keyword..." value="<?= e($logSearch) ?>">
                            </div>

                            <!-- Event Type Filter -->
                            <select class="form-control" id="logTypeFilter" style="width: auto; min-width: 170px; height: 38px; font-size: 12.5px;" onchange="onLogFilterChange()">
                                <option value="all" <?= $logType === '' || $logType === 'all' ? 'selected' : '' ?>>All Event Types</option>
                                <?php foreach ($distinctEmailTypes as $dType): ?>
                                    <option value="<?= e($dType) ?>" <?= $logType === $dType ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $dType))) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Delivery Status Filter -->
                            <select class="form-control" id="logStatusFilter" style="width: auto; min-width: 140px; height: 38px; font-size: 12.5px;" onchange="onLogFilterChange()">
                                <option value="all" <?= $logStatus === '' || $logStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="sent" <?= $logStatus === 'sent' ? 'selected' : '' ?>>✓ Sent</option>
                                <option value="failed" <?= $logStatus === 'failed' ? 'selected' : '' ?>>✕ Failed</option>
                            </select>

                            <!-- Date Range Filter -->
                            <select class="form-control" id="logDateFilter" style="width: auto; min-width: 130px; height: 38px; font-size: 12.5px;" onchange="onLogFilterChange()">
                                <option value="all" <?= $logDate === '' || $logDate === 'all' ? 'selected' : '' ?>>All Dates</option>
                                <option value="today" <?= $logDate === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="7days" <?= $logDate === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                                <option value="30days" <?= $logDate === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                            </select>

                            <button type="button" class="btn" style="height: 38px; padding: 0 14px; font-size: 12.5px;" onclick="resetLogFilters()">
                                Reset
                            </button>
                        </div>

                        <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                            Showing <span id="logVisibleCount" style="color: #0f172a; font-weight: 700;"><?= count($emailLogs) ?></span> of <?= $totalLogsCount ?> email logs
                        </div>
                    </div>
                </div>

                <div class="section-box">
                    <div class="table-responsive">
                        <table class="styled-fintech-table" id="emailLogsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Recipient Email</th>
                                    <th>Subject</th>
                                    <th>Event Type</th>
                                    <th>Delivery Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($emailLogs)): ?>
                                    <tr id="noEmailLogsRow"><td colspan="6" style="text-align: center; color: #94a3b8; padding: 36px;">No email audit records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($emailLogs as $idx => $log): ?>
                                        <tr class="log-row" 
                                            data-email="<?= strtolower(e($log['recipient_email'])) ?>" 
                                            data-subject="<?= strtolower(e($log['subject'])) ?>" 
                                            data-type="<?= e($log['type']) ?>" 
                                            data-status="<?= e($log['status']) ?>"
                                            data-time="<?= e($log['sent_at']) ?>">
                                            <td style="color:#94a3b8; font-size:12px; width:36px;"><?= $idx + 1 ?></td>
                                            <td><strong><?= e($log['recipient_email']) ?></strong></td>
                                            <td><?= e($log['subject']) ?></td>
                                            <td>
                                                <span class="badge info" style="text-transform: capitalize; background: #e0f2fe; color: #0369a1; border-radius: 6px; padding: 4px 8px; font-size: 11px;">
                                                    <?= e(str_replace('_', ' ', $log['type'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['status'] === 'sent'): ?>
                                                    <span class="status-badge-pill active"><span class="dot"></span> Sent</span>
                                                <?php else: ?>
                                                    <span class="status-badge-pill inactive"><span class="dot"></span> Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color: #64748b; font-size: 12px;"><?= date('M d, Y h:i A', strtotime($log['sent_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 3: FEE CATEGORY APPROVALS                -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'fee_approvals'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #fef3c7; color: #b45309;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Fee Category Approval Governance</h1>
                            <p class="portal-page-sub">Review, approve, or reject new fee categories requested by the Accounting Office.</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Section -->
                <div class="section-box" style="margin-bottom: 24px;">
                    <div class="section-box-header">
                        <div class="section-header-left">
                            <div class="section-header-icon" style="background:#fef3c7; color:#b45309;">⏳</div>
                            <h2 class="section-box-title">Pending Fee Category Requests (<?= $pendingFeeCount ?>)</h2>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>Fee Category Name</th>
                                    <th>Code</th>
                                    <th>Default Rate</th>
                                    <th>Type</th>
                                    <th>Requested By</th>
                                    <th>Requested At</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingApprovalsTbody">
                                <?php if (empty($pendingFeeCategories)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: #059669; padding: 36px; background: #f0fdf4;">
                                            <div style="font-size: 20px; margin-bottom: 6px;">✓</div>
                                            <strong>All fee category requests are up to date!</strong>
                                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">There are currently no pending fee requests from Accounting.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingFeeCategories as $pCat): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($pCat['name']) ?></strong>
                                            </td>
                                            <td><code><?= e($pCat['code']) ?></code></td>
                                            <td><strong style="color: #0b3d2e; font-size: 14px;"><?= peso((float)$pCat['default_amount']) ?></strong></td>
                                            <td>
                                                <span class="pill-badge <?= !empty($pCat['is_variable']) ? 'green' : 'blue' ?>">
                                                    <?= !empty($pCat['is_variable']) ? 'Variable (Tuition)' : 'Fixed Institutional' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= e($pCat['requester_name'] ?? $pCat['requester_username'] ?? 'Accounting Staff') ?></strong>
                                                <div style="font-size: 11px; color: #64748b;"><?= e($pCat['requester_email'] ?? '') ?></div>
                                            </td>
                                            <td style="color: #64748b; font-size: 12px;">
                                                <?= !empty($pCat['requested_at']) ? date('M d, Y h:i A', strtotime($pCat['requested_at'])) : '—' ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                    <!-- Approve Button -->
                                                    <form method="POST" action="<?= APP_URL ?>/public/admin/?view=fee_approvals" style="display:inline;" id="approveForm_<?= $pCat['id'] ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="approve_fee_category">
                                                        <input type="hidden" name="category_id" value="<?= $pCat['id'] ?>">
                                                        <button type="button" class="btn" style="background: #059669; color: #ffffff; padding: 6px 14px; font-weight: 700; font-size: 12px; border: none; border-radius: 6px;"
                                                            onclick="confirmApproveFee(<?= $pCat['id'] ?>, '<?= htmlspecialchars($pCat['name'], ENT_QUOTES) ?>')">
                                                            Approve ✓
                                                        </button>
                                                    </form>

                                                    <!-- Reject Button -->
                                                    <button type="button" class="btn" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 14px; font-weight: 700; font-size: 12px; border-radius: 6px;"
                                                        onclick="openRejectFeeModal(<?= $pCat['id'] ?>, '<?= htmlspecialchars($pCat['name'], ENT_QUOTES) ?>')">
                                                        Reject ✕
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- All Fee Categories Status Overview -->
                <div class="section-box">
                    <div class="section-box-header">
                        <div class="section-header-left">
                            <div class="section-header-icon" style="background:#eff6ff; color:#2563eb;">📚</div>
                            <h2 class="section-box-title">System Fee Categories Master Directory</h2>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category Name</th>
                                    <th>Code</th>
                                    <th>Rate</th>
                                    <th>Type</th>
                                    <th>Approval Status</th>
                                    <th>Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allFeeCategories as $idx => $catItem): ?>
                                    <tr>
                                        <td style="color:#94a3b8; font-size:12px;"><?= $idx + 1 ?></td>
                                        <td><strong><?= e($catItem['name']) ?></strong></td>
                                        <td><code><?= e($catItem['code']) ?></code></td>
                                        <td><strong><?= peso((float)$catItem['default_amount']) ?></strong></td>
                                        <td>
                                            <span class="pill-badge <?= !empty($catItem['is_variable']) ? 'green' : 'blue' ?>">
                                                <?= !empty($catItem['is_variable']) ? 'Variable' : 'Fixed' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($catItem['approval_status'] === 'approved'): ?>
                                                <span class="status-badge-pill active"><span class="dot"></span> Approved &amp; Active</span>
                                            <?php elseif ($catItem['approval_status'] === 'pending'): ?>
                                                <span class="status-badge-pill" style="background:#fef3c7; color:#b45309;"><span class="dot" style="background:#b45309;"></span> Pending Review</span>
                                            <?php else: ?>
                                                <span class="status-badge-pill inactive" title="<?= e($catItem['rejection_reason'] ?? 'Rejected') ?>"><span class="dot"></span> Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 12px;">
                                            <?= !empty($catItem['reviewed_at']) ? 'Reviewed ' . date('M d, Y', strtotime($catItem['reviewed_at'])) : (!empty($catItem['requested_at']) ? 'Requested ' . date('M d, Y', strtotime($catItem['requested_at'])) : date('M d, Y', strtotime($catItem['created_at']))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: ENROLL NEW STUDENT (WITH 3-WAY EMAIL NOTIFICATION) -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="enrollStudentModal">
    <div class="modal-window" style="max-width: 600px;">
        <button class="modal-close-x" onclick="document.getElementById('enrollStudentModal').classList.remove('active')">&times;</button>
        <h2 class="modal-header-title">Enroll New Student</h2>
        <p class="modal-header-sub">Creates student credentials and alerts Student, Parents, and Accounting Office.</p>

        <form method="POST" action="<?= APP_URL ?>/public/admin/?view=users" id="enrollStudentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_student">

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-size: 12px; color: #166534;">
                <strong>📢 Automated 3-Way Notification:</strong><br>
                1. <strong>Student</strong> receives ID &amp; default password (Last Name).<br>
                2. <strong>Parents</strong> receive login monitoring credentials.<br>
                3. <strong>Accounting Office</strong> receives an instant alert to assess tuition &amp; class details.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="form-group">
                    <label class="form-label" for="newStudentId">Student ID *</label>
                    <input type="text" class="form-control<?= $studentError('student_id') ?>" name="student_id" id="newStudentId" required placeholder="e.g. 2024-10001" pattern="\d{4}-\d{5}" value="<?= $studentValue('student_id') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="newStudentEmail">Student Email *</label>
                    <input type="email" pattern=".+@gmail\.com" title="Use a Gmail address ending in @gmail.com." class="form-control<?= $studentError('student_email') ?>" name="student_email" id="newStudentEmail" required placeholder="student@gmail.com" value="<?= $studentValue('student_email') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                <div class="form-group">
                    <label class="form-label" for="newFirstName">First Name *</label>
                    <input type="text" class="form-control<?= $studentError('first_name') ?>" name="first_name" id="newFirstName" required placeholder="First name" value="<?= $studentValue('first_name') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="newMiddleName">Middle Name</label>
                    <input type="text" class="form-control" name="middle_name" id="newMiddleName" placeholder="Optional" value="<?= $studentValue('middle_name') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="newLastName">Last Name *</label>
                    <input type="text" class="form-control<?= $studentError('last_name') ?>" name="last_name" id="newLastName" required placeholder="Last name" value="<?= $studentValue('last_name') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="form-group">
                    <label class="form-label" for="newCourse">Course / Program *</label>
                    <select class="form-control" name="course" id="newCourse">
                        <option value="BSCS" <?= $studentValue('course', 'BSCS') === 'BSCS' ? 'selected' : '' ?>>BS Computer Science</option>
                        <option value="BSIT" <?= $studentValue('course') === 'BSIT' ? 'selected' : '' ?>>BS Information Tech</option>
                        <option value="BSA" <?= $studentValue('course') === 'BSA' ? 'selected' : '' ?>>BS Accountancy</option>
                        <option value="BSEE" <?= $studentValue('course') === 'BSEE' ? 'selected' : '' ?>>BS Electrical Eng</option>
                        <option value="BSHM" <?= $studentValue('course') === 'BSHM' ? 'selected' : '' ?>>BS Hospitality Mgt</option>
                        <option value="BSIE" <?= $studentValue('course') === 'BSIE' ? 'selected' : '' ?>>BS Industrial Eng</option>
                        <option value="BSCrim" <?= $studentValue('course') === 'BSCrim' ? 'selected' : '' ?>>BS Criminology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="newSection">Section Code *</label>
                    <select class="form-control" name="section_code" id="newSection" required>
                        <?php foreach (Curriculum::getAllSectionCodes() as $group => $codes): ?>
                            <optgroup label="<?= htmlspecialchars($group, ENT_QUOTES) ?>">
                                <?php foreach ($codes as $code => $label): ?>
                                    <option value="<?= htmlspecialchars($code, ENT_QUOTES) ?>"
                                        <?= $studentValue('section_code', '11A1') === $code ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <small style="font-size:10.5px;color:#64748b;display:block;margin-top:3px;">
                        Format: <strong>[Year][Sem][Time: M/A/E][Type: 1-3]</strong> — Subjects based on Year &amp; Semester only
                    </small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="form-group">
                    <label class="form-label" for="newParentName">Parent / Guardian Name</label>
                    <input type="text" class="form-control" name="parent_name" id="newParentName" placeholder="Parent full name" value="<?= $studentValue('parent_name') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="newParentEmail">Parent Email</label>
                    <input type="email" pattern=".+@gmail\.com" title="Use a Gmail address ending in @gmail.com." class="form-control<?= $studentError('parent_email') ?>" name="parent_email" id="newParentEmail" placeholder="parent@gmail.com" value="<?= $studentValue('parent_email') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" for="newParentContact">Parent Contact Number</label>
                <input type="text" class="form-control" name="parent_contact" id="newParentContact" placeholder="09171234567" value="<?= $studentValue('parent_contact') ?>">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('enrollStudentModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn" id="btnEnrollStudentSubmit" style="background: #0b3d2e; color: #fff; font-weight: 700; padding: 10px 22px;">
                    <span class="submit-label">Enroll Student &amp; Send Emails</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: CREATE ACCOUNTING STAFF                       -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="createAccountingModal">
    <div class="modal-window" style="max-width: 480px;">
        <button class="modal-close-x" onclick="document.getElementById('createAccountingModal').classList.remove('active')">&times;</button>
        <h2 class="modal-header-title">Create Accounting Staff Account</h2>
        <p class="modal-header-sub">Grants access to student tuition assessments, receipts, and finance records.</p>

        <form method="POST" action="<?= APP_URL ?>/public/admin/?view=users" id="createAccountingForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_accounting">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="accUsername">Username *</label>
                <input type="text" class="form-control" name="username" id="accUsername" required placeholder="e.g. accounting_jane">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="accName">Staff Full Name *</label>
                <input type="text" class="form-control" name="name" id="accName" required placeholder="e.g. Jane Santos">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="accEmail">Staff Email *</label>
                <input type="email" pattern=".+@gmail\.com" title="Use a Gmail address ending in @gmail.com." class="form-control" name="email" id="accEmail" required placeholder="e.g. jane@gmail.com">
            </div>

            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="accPassword">Initial Password * (min. 6 characters)</label>
                <input type="password" class="form-control" name="password" id="accPassword" required minlength="6" placeholder="••••••••">
                <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">Credentials will be emailed directly to the staff member.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('createAccountingModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn" id="btnCreateAccSubmit" style="background: #0f172a; color: #fff; font-weight: 700; padding: 10px 22px;">
                    Create &amp; Email Credentials
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: REJECT FEE CATEGORY REQUEST -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="rejectFeeModal">
    <div class="modal-window" style="max-width: 480px;">
        <button class="modal-close-x" onclick="document.getElementById('rejectFeeModal').classList.remove('active')">&times;</button>
        <h2 class="modal-header-title" style="color: #dc2626;">Reject Fee Category Request</h2>
        <p class="modal-header-sub" id="rejectModalCatName">Category: Assessment Fee</p>

        <form method="POST" action="<?= APP_URL ?>/public/admin/?view=fee_approvals" id="rejectFeeForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject_fee_category">
            <input type="hidden" name="category_id" id="rejectCategoryId" value="">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="rejectionReason">Reason for Disapproval *</label>
                <textarea class="form-control" name="rejection_reason" id="rejectionReason" required rows="3" placeholder="Provide explanation for accounting staff (e.g. Duplicate institutional charge, invalid rate amount, or requires board approval)..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('rejectFeeModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn" style="background: #dc2626; color: #fff; font-weight: 700; padding: 9px 20px;">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Admin Loading Overlay ── -->
<div id="adminLoadingOverlay" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.72); backdrop-filter:blur(4px); flex-direction:column; align-items:center; justify-content:center; gap:22px;">
    <div style="width:56px;height:56px;border-radius:50%;border:5px solid rgba(255,255,255,0.18);border-top-color:#fff;animation:adminSpin 0.85s linear infinite;"></div>
    <div style="text-align:center;">
        <div id="adminLoadingTitle" style="color:#fff;font-size:18px;font-weight:700;margin-bottom:6px;">Processing…</div>
        <div id="adminLoadingDesc" style="color:rgba(255,255,255,0.7);font-size:13.5px;max-width:320px;line-height:1.5;">Please wait while we process your request.</div>
    </div>
</div>
<style>
@keyframes adminSpin { to { transform: rotate(360deg); } }
</style>

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

    // Logout
    document.getElementById('btnLogout')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Sign Out?',
            text: 'Are you sure you want to end your administrator session?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Sign Out'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= APP_URL ?>/public/?action=logout';
            }
        });
    });

    // ── Modals ──
    window.openEnrollStudentModal = function() {
        document.getElementById('enrollStudentModal')?.classList.add('active');
    };
    window.openCreateAccountingModal = function() {
        document.getElementById('createAccountingModal')?.classList.add('active');
    };

    // ── SweetAlert: Suspend / Activate User ──
    window.confirmStatusToggle = function(userId, name, currentStatus) {
        const isSuspending = currentStatus === 'active';
        Swal.fire({
            title: isSuspending ? 'Suspend Account?' : 'Activate Account?',
            html: isSuspending
                ? `Are you sure you want to <strong>suspend</strong> the account of <strong>${name}</strong>?<br><br><span style="color:#b45309;font-size:12px;">The user will no longer be able to log in until reactivated.</span>`
                : `Are you sure you want to <strong>reactivate</strong> the account of <strong>${name}</strong>?<br><br><span style="color:#059669;font-size:12px;">The user will regain access to their portal.</span>`,
            icon: isSuspending ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isSuspending ? '#b45309' : '#0b3d2e',
            cancelButtonColor: '#64748b',
            confirmButtonText: isSuspending ? 'Yes, Suspend' : 'Yes, Activate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('statusForm_' + userId)?.submit();
            }
        });
    };

    const adminLoadingOverlay = document.getElementById('adminLoadingOverlay');
    const adminLoadingTitle   = document.getElementById('adminLoadingTitle');
    const adminLoadingDesc    = document.getElementById('adminLoadingDesc');

    const enrollStudentForm = document.getElementById('enrollStudentForm');
    enrollStudentForm?.addEventListener('submit', () => {
        const submit = document.getElementById('btnEnrollStudentSubmit');
        if (!submit || !enrollStudentForm.checkValidity()) return;
        submit.disabled = true;
        submit.querySelector('.submit-label').textContent = 'Processing…';
        if (adminLoadingTitle) adminLoadingTitle.textContent = 'Enrolling Student…';
        if (adminLoadingDesc)  adminLoadingDesc.textContent  = 'Creating student account and sending welcome emails. Please wait.';
        if (adminLoadingOverlay) adminLoadingOverlay.style.display = 'flex';
    });

    const createAccountingForm = document.getElementById('createAccountingForm');
    const btnCreateAccSubmit   = document.getElementById('btnCreateAccSubmit');
    createAccountingForm?.addEventListener('submit', () => {
        if (!createAccountingForm.checkValidity()) return;
        if (btnCreateAccSubmit) { btnCreateAccSubmit.disabled = true; btnCreateAccSubmit.textContent = 'Processing…'; }
        if (adminLoadingTitle) adminLoadingTitle.textContent = 'Creating Account…';
        if (adminLoadingDesc)  adminLoadingDesc.textContent  = 'Creating accounting staff account and sending credentials by email. Please wait.';
        if (adminLoadingOverlay) adminLoadingOverlay.style.display = 'flex';
    });

    // ── Delete User Confirmation ──
    window.confirmDeleteUser = function(userId, name, username) {
        Swal.fire({
            title: 'Delete User Account?',
            html: `Are you sure you want to permanently delete account <strong>${name}</strong> (<code>${username}</code>)?<br><br><span style="color:#ef4444;font-size:12px;">Warning: Associated records will be removed. This action cannot be undone.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Permanently Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= APP_URL ?>/public/admin/?view=users';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_token';
                csrf.value = '<?= csrf_token() ?>';
                form.appendChild(csrf);

                const act = document.createElement('input');
                act.type = 'hidden';
                act.name = 'action';
                act.value = 'delete_user';
                form.appendChild(act);

                const uid = document.createElement('input');
                uid.type = 'hidden';
                uid.name = 'user_id';
                uid.value = userId;
                form.appendChild(uid);

                document.body.appendChild(form);
                form.submit();
            }
        });
    };

    // ── Unified Filter Handler (Tabs, Dropdowns, Searches) ──
    let currentRoleFilter = 'all';
    let currentStatusFilter = 'all';
    let currentSearchQuery = '';

    function applyAllFilters() {
        const rows = document.querySelectorAll('#usersTable tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');
            const text = row.textContent.toLowerCase();

            const matchRole = (currentRoleFilter === 'all' || role === currentRoleFilter);
            const matchStatus = (currentStatusFilter === 'all' || status === currentStatusFilter);
            const matchQuery = (!currentSearchQuery || text.includes(currentSearchQuery));

            if (matchRole && matchStatus && matchQuery) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const countDisplay = document.getElementById('countDisplay');
        if (countDisplay) countDisplay.textContent = visibleCount;
    }

    // Tab Pill filter
    window.filterByTabPill = function(role, btn) {
        currentRoleFilter = role;
        document.querySelectorAll('.filter-tab-pill').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const roleSelect = document.getElementById('roleDropdownFilter');
        if (roleSelect) roleSelect.value = role;

        applyAllFilters();
    };

    // Dropdown filters (Role & Status)
    window.filterByDropdowns = function() {
        const roleSelect = document.getElementById('roleDropdownFilter');
        const statusSelect = document.getElementById('statusDropdownFilter');

        if (roleSelect) {
            currentRoleFilter = roleSelect.value;
            document.querySelectorAll('.filter-tab-pill').forEach(b => {
                b.classList.toggle('active', b.textContent.toLowerCase().includes(currentRoleFilter) || (currentRoleFilter === 'all' && b.textContent.includes('All')));
            });
        }
        if (statusSelect) {
            currentStatusFilter = statusSelect.value;
        }
        applyAllFilters();
    };

    // Live Search Listeners (Topbar & Table Inline)
    const adminUserSearch = document.getElementById('adminUserSearch');
    const inlineAccountSearch = document.getElementById('inlineAccountSearch');

    function onSearchInput(val) {
        currentSearchQuery = val.toLowerCase().trim();
        if (adminUserSearch && adminUserSearch.value !== val) adminUserSearch.value = val;
        if (inlineAccountSearch && inlineAccountSearch.value !== val) inlineAccountSearch.value = val;
        applyAllFilters();
    }

    adminUserSearch?.addEventListener('input', e => onSearchInput(e.target.value));
    inlineAccountSearch?.addEventListener('input', e => onSearchInput(e.target.value));

    // The top search also works from Home and Logs: Enter opens the user directory
    // with the same query, then the live filter is applied to the accounts table.
    adminUserSearch?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !document.getElementById('usersTable')) {
            const query = adminUserSearch.value.trim();
            if (query) window.location.href = '<?= APP_URL ?>/public/admin/?view=users&search=' + encodeURIComponent(query);
        }
    });
    if (adminUserSearch?.value && document.getElementById('usersTable')) onSearchInput(adminUserSearch.value);

    // Ctrl + K Shortcut
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            adminUserSearch?.focus();
            adminUserSearch?.select();
        }
    });

    // Notification dropdown
    const btnAdminNotif = document.getElementById('btnAdminNotif');
    const adminNotifDropdown = document.getElementById('adminNotifDropdown');
    btnAdminNotif?.addEventListener('click', (e) => {
        e.stopPropagation();
        adminNotifDropdown?.classList.toggle('open');
        btnAdminNotif.classList.toggle('active');
    });
    document.addEventListener('click', () => {
        adminNotifDropdown?.classList.remove('open');
        btnAdminNotif?.classList.remove('active');
    });

    // ── SweetAlert / Approval for Fee Categories ──
    window.confirmApproveFee = function(catId, catName) {
        Swal.fire({
            title: 'Approve Fee Category?',
            html: `Are you sure you want to approve and activate <strong>${catName}</strong>?<br><br><span style="color:#059669;font-size:12.5px;">This fee category will become active and available for student tuition assessments.</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Approve Fee',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approveForm_' + catId)?.submit();
            }
        });
    };

    window.openRejectFeeModal = function(catId, catName) {
        document.getElementById('rejectCategoryId').value = catId;
        document.getElementById('rejectModalCatName').textContent = 'Category: ' + catName;
        document.getElementById('rejectionReason').value = '';
        document.getElementById('rejectFeeModal')?.classList.add('active');
    };

    // ── Email Logs Real-Time Client & URL Filtering ──
    const logSearchInput = document.getElementById('logSearchInput');
    const logTypeFilter = document.getElementById('logTypeFilter');
    const logStatusFilter = document.getElementById('logStatusFilter');
    const logDateFilter = document.getElementById('logDateFilter');
    const logVisibleCount = document.getElementById('logVisibleCount');

    function applyEmailLogFilters() {
        const rows = document.querySelectorAll('#emailLogsTable tbody tr.log-row');
        if (!rows.length) return;

        const q = (logSearchInput?.value || '').toLowerCase().trim();
        const type = logTypeFilter?.value || 'all';
        const status = logStatusFilter?.value || 'all';
        const dateRange = logDateFilter?.value || 'all';

        const now = new Date();
        let visible = 0;

        rows.forEach(row => {
            const rowEmail = row.getAttribute('data-email') || '';
            const rowSubj = row.getAttribute('data-subject') || '';
            const rowType = row.getAttribute('data-type') || '';
            const rowStatus = row.getAttribute('data-status') || '';
            const rowTimeStr = row.getAttribute('data-time') || '';
            const rowDate = new Date(rowTimeStr);

            const matchSearch = !q || rowEmail.includes(q) || rowSubj.includes(q) || rowType.includes(q);
            const matchType = (type === 'all' || rowType === type);
            const matchStatus = (status === 'all' || rowStatus === status);

            let matchDate = true;
            if (dateRange === 'today') {
                matchDate = rowDate.toDateString() === now.toDateString();
            } else if (dateRange === '7days') {
                const diffDays = (now - rowDate) / (1000 * 60 * 60 * 24);
                matchDate = diffDays <= 7;
            } else if (dateRange === '30days') {
                const diffDays = (now - rowDate) / (1000 * 60 * 60 * 24);
                matchDate = diffDays <= 30;
            }

            if (matchSearch && matchType && matchStatus && matchDate) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        if (logVisibleCount) logVisibleCount.textContent = visible;

        let noRow = document.getElementById('noEmailLogsRow');
        if (visible === 0) {
            if (!noRow) {
                const tbody = document.querySelector('#emailLogsTable tbody');
                noRow = document.createElement('tr');
                noRow.id = 'noEmailLogsRow';
                noRow.innerHTML = '<td colspan="6" style="text-align: center; color: #94a3b8; padding: 36px;">No email audit records matched your filter.</td>';
                tbody.appendChild(noRow);
            } else {
                noRow.style.display = '';
            }
        } else if (noRow) {
            noRow.style.display = 'none';
        }
    }

    logSearchInput?.addEventListener('input', applyEmailLogFilters);
    window.onLogFilterChange = applyEmailLogFilters;

    window.resetLogFilters = function() {
        if (logSearchInput) logSearchInput.value = '';
        if (logTypeFilter) logTypeFilter.value = 'all';
        if (logStatusFilter) logStatusFilter.value = 'all';
        if (logDateFilter) logDateFilter.value = 'all';
        applyEmailLogFilters();
    };

    <?php if (!empty($studentFormErrors)): ?>
        openEnrollStudentModal();
    <?php endif; ?>
    <?php if (!empty($accountCreated)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Account created',
            text: <?= json_encode(($accountCreated['type'] === 'accounting' ? 'Accounting staff' : 'Student') . ' account for ' . $accountCreated['name'] . ' was created and credentials were sent by email.') ?>,
            confirmButtonColor: '#0b3d2e'
        });
    <?php endif; ?>
        // ── Real-Time Live Feed for Admin Fee Approvals via AJAX (every 2.5 seconds) ──
    let lastAdminApprovalsHash = null;

    function renderAdminPendingRowHtml(p) {
        const isVar = p.is_variable == 1;
        const typeBadge = isVar
            ? '<span class="pill-badge green">Variable (Tuition)</span>'
            : '<span class="pill-badge blue">Fixed Institutional</span>';
        const reqName = (p.requester_name || p.requester_username || 'Accounting Office');
        const reqEmail = (p.requester_email || '');
        const reqDate = p.requested_at || 'Just now';
        const amount = '₱' + parseFloat(p.default_amount).toLocaleString('en-US', { minimumFractionDigits: 2 });

        return `
            <tr>
                <td><strong>${p.name}</strong></td>
                <td><code>${p.code}</code></td>
                <td><strong style="color: #0b3d2e; font-size: 14px;">${amount}</strong></td>
                <td>${typeBadge}</td>
                <td>
                    <strong>${reqName}</strong>
                    <div style="font-size: 11px; color: #64748b;">${reqEmail}</div>
                </td>
                <td style="color: #64748b; font-size: 12px;">${reqDate}</td>
                <td style="text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                        <form method="POST" action="<?= APP_URL ?>/public/admin/?view=fee_approvals" style="display:inline;" id="approveForm_${p.id}">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="approve_fee_category">
                            <input type="hidden" name="category_id" value="${p.id}">
                            <button type="button" class="btn" style="background: #059669; color: #ffffff; padding: 6px 14px; font-weight: 700; font-size: 12px; border: none; border-radius: 6px;"
                                onclick="confirmApproveFee(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                                Approve ✓
                            </button>
                        </form>
                        <button type="button" class="btn" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 14px; font-weight: 700; font-size: 12px; border-radius: 6px;"
                            onclick="openRejectFeeModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                            Reject ✕
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function pollAdminLiveAjax() {
        $.ajax({
            url: '<?= APP_URL ?>/public/admin/?action=live_fee_approvals&_t=' + Date.now(),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(data) {
                if (!data || !data.success) return;

                if (lastAdminApprovalsHash !== null && lastAdminApprovalsHash !== data.hash) {
                    const tbody = document.getElementById('pendingApprovalsTbody');
                    if (tbody) {
                        if (!data.pending_categories || data.pending_categories.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #059669; padding: 36px; background: #f0fdf4;">
                                        <div style="font-size: 20px; margin-bottom: 6px;">✓</div>
                                        <strong>All fee category requests are up to date!</strong>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">There are currently no pending fee requests from Accounting.</div>
                                    </td>
                                </tr>
                            `;
                        } else {
                            tbody.innerHTML = data.pending_categories.map(renderAdminPendingRowHtml).join('');
                        }
                    }

                    // Update title counter
                    const headerTitle = document.querySelector('.section-box-title');
                    if (headerTitle && headerTitle.textContent.includes('Pending Fee Category Requests')) {
                        headerTitle.textContent = `Pending Fee Category Requests (${data.pending_count})`;
                    }

                    // Update nav badge
                    const navBadge = document.querySelector('a[href*="view=fee_approvals"] .badge');
                    if (navBadge) {
                        if (data.pending_count > 0) {
                            navBadge.textContent = `${data.pending_count} pending`;
                            navBadge.style.display = 'inline-block';
                        } else {
                            navBadge.style.display = 'none';
                        }
                    }

                    // Toast
                    if (window.Swal && data.pending_count > 0) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4500,
                            timerProgressBar: true
                        }).fire({
                            icon: 'warning',
                            title: `Fee Approval Requests updated in real-time (${data.pending_count} pending)`
                        });
                    }
                }
                lastAdminApprovalsHash = data.hash;
            }
        });
    }

    // Poll every 2.5 seconds
    setInterval(pollAdminLiveAjax, 2500);
    window.addEventListener('focus', pollAdminLiveAjax);
</script>

<!-- Bootstrap 5.3 JS (CDN with Subresource Integrity & Local Fallback) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= APP_URL ?>/assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
