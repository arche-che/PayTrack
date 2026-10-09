<?php
/**
 * PayTrack — Student Portal
 * Multi-Page View Routing: 'home' (Overview), 'fees' (Tuition & Assessment), 'history' (Payment History), 'receipts' (Official Receipts)
 * Preserves the client-approved light sidebar with active link indicators
 */
$studentName = $student ? ($student['first_name'] . ' ' . $student['last_name']) : 'Student';
$studentNum  = $student ? $student['student_id'] : '2023-53512';
$studentInitial = strtoupper(substr($student['first_name'] ?? 'J', 0, 1));

$primaryFee = !empty($fees) ? $fees[0] : null;
$breakdownItems = $primaryFee ? ($primaryFee['items'] ?? []) : [];
$termDescription = $primaryFee['description'] ?? 'S.Y. ' . ($student['school_year'] ?? (date('Y') . '-' . (date('Y') + 1))) . ' - Assessment Pending';
$isPendingAssessment = empty($fees) || !$primaryFee;

$totalFees = $primaryFee ? (float)$primaryFee['total_amount'] : 0.0;
$totalPaid = $primaryFee ? (float)$primaryFee['amount_paid'] : 0.0;
$totalRemaining = max(0.0, $totalFees - $totalPaid);
$calcPct = ($totalFees > 0) ? min(100, round(($totalPaid / $totalFees) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>PayTrack — Student Portal</title>
    <!-- Bootstrap 5.3 (Local & CDN with Subresource Integrity) -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/bootstrap/css/bootstrap.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- jQuery 3.7.1 for AJAX Operations -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <style>
        body {
            background-color: #f8fafc;
        }

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

        /* Topbar additions */
        .search-container {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 16px;
            width: 100%;
            max-width: 400px;
            gap: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .search-container input {
            border: none;
            outline: none;
            font-size: 12.5px;
            color: #1e293b;
            width: 100%;
            background: transparent;
        }
        .search-container input::placeholder {
            color: #94a3b8;
        }

        /* ── Live Search Dropdown ── */
        .search-live-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            min-width: 340px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
            z-index: 99999;
            max-height: 380px;
            overflow-y: auto;
            padding: 6px 0;
            text-align: left;
        }
        .search-dropdown-group-title {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            padding: 8px 14px 4px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }
        .search-dropdown-group-title:first-child {
            border-top: none;
        }
        .search-dropdown-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 14px;
            cursor: pointer;
            font-size: 12.5px;
            color: #0f172a;
            transition: background 0.12s ease;
            text-decoration: none;
            border-bottom: 1px solid #f8fafc;
        }
        .search-dropdown-item:hover {
            background: #eff6ff;
        }
        .search-dropdown-item .item-meta {
            font-size: 11px;
            color: #64748b;
        }
        .search-dropdown-empty {
            padding: 16px 14px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .notif-wrapper {
            position: relative;
            display: inline-block;
        }

        .notif-bell-btn {
            position: relative;
            width: 40px;
            height: 40px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            outline: none;
            user-select: none;
        }
        .notif-bell-btn:hover {
            background: #ecfdf5;
            border-color: #10b981;
            color: #0b3d2e;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.2);
        }
        .notif-bell-btn:hover svg {
            transform: scale(1.1) rotate(12deg);
            stroke: #0b3d2e;
        }
        .notif-bell-btn:active {
            transform: translateY(0) scale(0.95);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .notif-bell-btn.active {
            background: #0b3d2e;
            border-color: #0b3d2e;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(11, 61, 46, 0.25);
        }
        .notif-bell-btn.active svg {
            stroke: #ffffff;
        }
        .notif-bell-btn svg {
            transition: transform 0.2s ease, stroke 0.2s ease;
        }
        .notif-dot-red {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 9px;
            height: 9px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 1.5px rgba(239, 68, 68, 0.35);
            animation: notifDotPulse 2s infinite ease-in-out;
        }
        @keyframes notifDotPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.25); opacity: 0.85; }
        }

        /* Notification Dropdown Menu */
        .notif-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 380px;
            max-width: calc(100vw - 24px);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.16), 0 6px 14px -2px rgba(15, 23, 42, 0.08);
            z-index: 1050;
            overflow: hidden;
            display: none;
            transform-origin: top right;
            animation: notifDropdownPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes notifDropdownPop {
            0% { opacity: 0; transform: translateY(-8px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .notif-header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
        }
        .notif-header-badge {
            font-size: 11px;
            font-weight: 700;
            background: #0b3d2e;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .notif-btn-clear {
            background: transparent;
            border: none;
            color: #059669;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .notif-btn-clear:hover {
            background: #ecfdf5;
            color: #0b3d2e;
            text-decoration: underline;
        }
        .notif-list-body {
            max-height: 380px;
            overflow-y: auto;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .notif-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s ease;
            position: relative;
        }
        .notif-row:hover {
            background: #f8fafc;
        }
        .notif-row.unread {
            background: #f0fdf4;
        }
        .notif-row.unread:hover {
            background: #e6f9ed;
        }
        .notif-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .notif-icon-circle.success {
            background: #dcfce7;
            color: #166534;
        }
        .notif-icon-circle.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .notif-icon-circle.info {
            background: #e0f2fe;
            color: #0369a1;
        }
        .notif-text {
            flex: 1;
            min-width: 0;
        }
        .notif-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .notif-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 4px;
        }
        .notif-meta-time {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .notif-item-pill {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            margin-top: 6px;
            flex-shrink: 0;
        }
        .notif-empty-state {
            padding: 36px 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }
        .notif-footer {
            padding: 10px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .notif-footer a {
            font-size: 12px;
            font-weight: 700;
            color: #0b3d2e;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .notif-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .notif-dropdown-menu {
                position: fixed;
                top: 64px;
                left: 12px;
                right: 12px;
                width: auto;
                max-width: none;
                border-radius: 12px;
            }
        }

        .user-chip-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px;
            cursor: default;
        }
        .user-avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #1e3a8a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .user-chip-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }
        .user-chip-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .user-chip-id {
            font-size: 11px;
            color: #64748b;
        }

        /* Header layout */
        .portal-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .portal-title-flex {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .wallet-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(37,99,235,0.08);
        }
        .portal-page-title {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }
        .portal-page-sub {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0 0;
        }

        .btn-pay-now-main {
            background: #1d4ed8;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 700;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 4px 12px rgba(29,78,216,0.22);
            will-change: transform;
        }
        .btn-pay-now-main:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(29,78,216,0.32);
        }
        .btn-pay-now-main:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* 2-Column Hero Grid */
        .hero-dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1.05fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 960px) {
            .hero-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card 1: Balance Card */
        .card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            position: relative;
        }
        .card-eyebrow {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .card-term-sub {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .balance-amount-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .balance-huge-red {
            font-size: 28px;
            font-weight: 900;
            color: #e11d48;
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .btn-pay-pill-dark {
            background: #1e3a8a;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-pay-pill-dark:hover {
            background: #172554;
            transform: translateY(-1px);
        }
        .balance-meta-bullet {
            font-size: 12px;
            color: #0f172a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .balance-meta-bullet span.dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #0f172a;
            display: inline-block;
        }

        .progress-track-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .progress-bar-track {
            flex: 1;
            height: 7px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar-fill-green {
            height: 100%;
            background: #10b981;
            border-radius: 9999px;
            transition: width 0.4s ease;
        }
        .progress-pct-label {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Card 2: Inspiration Quote Banner */
        .motivation-hero-card {
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 50%, #e2e8f0 100%);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 155px;
        }
        .motivation-text-col {
            position: relative;
            z-index: 2;
            max-width: 250px;
        }
        .motivation-quote-text {
            font-size: 15.5px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
            font-style: italic;
        }
        .quote-accent-bar {
            width: 30px;
            height: 3px;
            background: #2563eb;
            border-radius: 2px;
            margin-top: 10px;
        }

        .motivation-gfx-col {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .floating-grad-badge {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(37,99,235,0.12);
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(37,99,235,0.15);
        }

        /* Sections & Tables */
        .section-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .section-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }
        .section-box-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .section-box-meta {
            font-size: 12px;
            color: #64748b;
        }

        /* Clean Styled Table */
        .styled-fintech-table {
            width: 100% !important;
            min-width: 0 !important;
            border-collapse: collapse;
            table-layout: auto;
        }
        .styled-fintech-table thead tr {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .styled-fintech-table thead th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            white-space: nowrap;
        }
        .styled-fintech-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s ease;
        }
        .styled-fintech-table tbody tr:hover {
            background: #fbfcfe;
        }
        .styled-fintech-table tbody td {
            padding: 11px 12px;
            font-size: 13px;
            color: #1e293b;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Aspect Item Icons */
        .aspect-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: #0f172a;
        }
        .aspect-icon-badge {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .aspect-icon-badge.blue {
            background: #eff6ff;
            color: #2563eb;
        }
        .aspect-icon-badge.purple {
            background: #faf5ff;
            color: #9333ea;
        }
        .aspect-icon-badge.green {
            background: #ecfdf5;
            color: #059669;
        }

        /* Classification Badges */
        .pill-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .pill-badge.blue {
            background: #eff6ff;
            color: #2563eb;
        }
        .pill-badge.purple {
            background: #faf5ff;
            color: #9333ea;
        }
        .pill-badge.green {
            background: #ecfdf5;
            color: #059669;
        }

        /* Total Row */
        .table-total-row {
            background: #ffffff !important;
            border-top: 1px solid #e2e8f0;
            border-bottom: none !important;
            font-weight: 700;
        }
        .table-total-row td {
            padding: 16px 16px 8px !important;
        }

        /* Notice Banner */
        .green-notice-callout {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 18px;
            margin-top: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .green-notice-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #10b981;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .green-notice-body {
            font-size: 12.5px;
            line-height: 1.5;
            color: #166534;
        }
        .green-notice-body strong.notice-title {
            display: block;
            font-size: 12.5px;
            color: #14532d;
            margin-bottom: 2px;
        }

        /* Payment history action button */
        .btn-view-receipt-outline {
            background: #ffffff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            padding: 5px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-view-receipt-outline:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .badge-method-online {
            background: #ecfdf5;
            color: #059669;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Metric Mini Cards for History Page */
        .metrics-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) {
            .metrics-grid-3 {
                grid-template-columns: 1fr;
            }
        }
        .metric-mini-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .metric-mini-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
        }
        .metric-mini-val {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .btn-logout-prominent {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            background: #fee2e2;
            border: 1.5px solid #f87171;
            border-radius: 8px;
            color: #b91c1c;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-logout-prominent:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #dc2626;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        /* ── Official Print Styling for National College of Science and Technology ── */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .sidebar, .topbar, .portal-header-row, .metrics-grid-3, .section-box,
            .hero-dashboard-grid, .modal-backdrop:not(#officialReceiptModal),
            .no-print, #sidebarOverlay, .notif-bell-btn, .search-container {
                display: none !important;
            }
            #officialReceiptModal {
                position: static !important;
                display: block !important;
                background: #ffffff !important;
                width: 100% !important;
                height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 999999 !important;
            }
            #officialReceiptModal .modal-window {
                position: static !important;
                transform: none !important;
                box-shadow: none !important;
                border: 2px solid #0b3d2e !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }
            #ncstPrintableReceipt {
                padding: 24px 30px !important;
                background: #ffffff !important;
            }
            @page {
                size: portrait;
                margin: 10mm;
            }
        }
        /* ── Mobile Responsive ── */
        @media (max-width: 768px) {
            .portal-header-row { gap: 12px; flex-direction: column; align-items: flex-start; }
            .search-container { max-width: 150px; }
            .search-container input { font-size: 11.5px; }

            .metrics-grid-3 { grid-template-columns: 1fr 1fr; }

            .hero-dashboard-grid { grid-template-columns: 1fr; }

            .section-box-header { flex-wrap: wrap; gap: 8px; }

            /* Pay tuition button full-width on small screens */
            .btn-pay-now-main { width: 100% !important; justify-content: center; }

            /* Receipt cards: ensure single col */
            .receipt-cards-grid { grid-template-columns: 1fr !important; }

            /* Styled fintech table: compact on mobile */
            .styled-fintech-table thead th {
                padding: 8px 8px;
                font-size: 10px;
            }
            .styled-fintech-table tbody td {
                padding: 10px 8px;
                font-size: 12px;
            }
            .btn-view-receipt-outline {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .metrics-grid-3 { grid-template-columns: 1fr; }
            .search-container { max-width: 110px; }
            .portal-page-title { font-size: 18px !important; }
            /* Hide method column in recent payments on tiny screens to avoid scroll */
            .styled-fintech-table th:nth-child(3),
            .styled-fintech-table td:nth-child(3) {
                display: none;
            }
        }

    </style>
</head>
<body>

<div class="app-shell">
    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar (Client-Approved Light Sidebar) -->
    <aside class="sidebar" id="sidebar">
        <div class="org">
            <div class="org-icon">S</div>
            <div class="org-text">
                <div class="org-name">PayTrack</div>
                <div class="org-team">Student Portal</div>
            </div>
            <button class="mobile-menu-btn" id="btnCloseSidebar" style="margin-left: auto; width: 28px; height: 28px; font-size: 14px;">&times;</button>
        </div>

        <ul class="nav">
            <li>
                <a href="<?= APP_URL ?>/public/student/?view=home" class="nav-item <?= $currentView === 'home' ? 'active' : '' ?>">
                    <span class="ic">&#8962;</span> Home
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/student/?view=fees" class="nav-item <?= $currentView === 'fees' ? 'active' : '' ?>">
                    <span class="ic">&#128179;</span> My Tuition &amp; Balance
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/student/?view=history" class="nav-item <?= $currentView === 'history' ? 'active' : '' ?>">
                    <span class="ic">&#8644;</span> Payment History
                    <span class="badge-count"><?= count($payments) ?></span>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/student/?view=receipts" class="nav-item <?= $currentView === 'receipts' ? 'active' : '' ?>">
                    <span class="ic">&#128196;</span> Official Receipts
                    <span class="badge-count"><?= count($payments) ?></span>
                </a>
            </li>
            <li>
                <a href="<?= APP_URL ?>/public/student/?view=refunds" class="nav-item <?= $currentView === 'refunds' ? 'active' : '' ?>">
                    <span class="ic">&#8635;</span> Refund Requests
                    <?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?>
                        <span class="badge-count" style="background:#d97706;"><?= $pendingRefundsCount ?></span>
                    <?php elseif (!empty($refunds) && count($refunds) > 0): ?>
                        <span class="badge-count"><?= count($refunds) ?></span>
                    <?php endif; ?>
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
        <!-- Topbar -->
        <header class="topbar" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;">
            <button class="mobile-menu-btn" id="btnOpenSidebar" aria-label="Toggle Navigation">&#9776;</button>

            <!-- Search Bar -->
            <div class="search-container" style="position: relative;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="studentSearchInput" placeholder="Search my records (OR#, fee, amount)..." autocomplete="off" value="<?= e($_GET['q'] ?? '') ?>">
                <div id="studentSearchDropdown" class="search-live-dropdown" style="display: none;"></div>
            </div>

            <!-- Topbar Right Profile & Notif Actions -->
            <div class="topbar-actions" style="display: flex; align-items: center; gap: 12px;">
                <?php
                // Generate Dynamic Student Notifications
                $studentNotifs = [];

                // 1. Recent Payments
                if (!empty($payments)) {
                    foreach (array_slice($payments, 0, 2) as $p) {
                        $studentNotifs[] = [
                            'type' => 'success',
                            'icon' => '🧾',
                            'title' => 'Payment Verified (OR# ' . $p['or_number'] . ')',
                            'desc' => 'Payment of ' . peso($p['amount']) . ' via ' . strtoupper($p['payment_method']) . ' has been recorded.',
                            'time' => date('M d, Y h:i A', strtotime($p['created_at'] ?? 'now')),
                            'link' => APP_URL . '/public/student/?view=receipt&or=' . urlencode($p['or_number']),
                            'unread' => true,
                        ];
                    }
                }

                // 2. Assessment status. A newly registered student has a zero balance
                // only because Accounting has not posted an assessment yet—not because
                // tuition has been paid in full.
                if ($isPendingAssessment) {
                    $studentNotifs[] = [
                        'type' => 'warning',
                        'icon' => '⏳',
                        'title' => 'Tuition Assessment Pending',
                        'desc' => 'Your account is registered and is waiting for the Accounting Office to post your tuition assessment.',
                        'time' => 'Account Setup',
                        'link' => APP_URL . '/public/student/?view=fees',
                        'unread' => true,
                    ];
                } elseif ($totalRemaining > 0) {
                    $studentNotifs[] = [
                        'type' => 'warning',
                        'icon' => '💳',
                        'title' => 'Outstanding Tuition Assessment',
                        'desc' => 'You have a remaining balance of ' . peso($totalRemaining) . '. Pay online or review your breakdown.',
                        'time' => 'Active Term Notice',
                        'link' => APP_URL . '/public/student/?view=fees',
                        'unread' => true,
                    ];
                } else {
                    $studentNotifs[] = [
                        'type' => 'success',
                        'icon' => '🎉',
                        'title' => 'Tuition Fully Cleared',
                        'desc' => 'Great news! All tuition assessments for the active academic period are settled.',
                        'time' => 'Account Status',
                        'link' => APP_URL . '/public/student/?view=fees',
                        'unread' => false,
                    ];
                }

                // 3. Official Clearance Advisory
                $studentNotifs[] = [
                    'type' => 'info',
                    'icon' => '📢',
                    'title' => 'Exam Clearance & Official Receipts',
                    'desc' => 'Official electronic receipts are recognized institutional proofs for exam permit validation.',
                    'time' => 'Registrar Notice',
                    'link' => APP_URL . '/public/student/?view=receipts',
                    'unread' => false,
                ];

                // 4. Shared Password Info
                $studentNotifs[] = [
                    'type' => 'info',
                    'icon' => '🛡️',
                    'title' => 'Student & Parent Access Note',
                    'desc' => 'Your login password is permanently linked to your Last Name for shared parent tuition monitoring.',
                    'time' => 'Policy Advisory',
                    'link' => '#',
                    'unread' => false,
                ];

                $unreadCount = 0;
                foreach ($studentNotifs as $nItem) {
                    if (!empty($nItem['unread'])) $unreadCount++;
                }
                ?>

                <div class="notif-wrapper">
                    <button type="button" class="notif-bell-btn" id="btnStudentNotif" aria-label="Notifications" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-dot-red" id="notifDotBadge"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown Panel -->
                    <div class="notif-dropdown-menu" id="notifDropdownMenu" role="region" aria-label="Notifications">
                        <div class="notif-header">
                            <div class="notif-header-title">
                                <span>🔔 Notifications</span>
                                <span class="notif-header-badge" id="notifBadgeCount"><?= $unreadCount ?> new</span>
                            </div>
                            <?php if ($unreadCount > 0): ?>
                                <button type="button" class="notif-btn-clear" id="btnMarkAllRead">Mark all as read</button>
                            <?php endif; ?>
                        </div>

                        <ul class="notif-list-body">
                            <?php if (empty($studentNotifs)): ?>
                                <li class="notif-empty-state">
                                    <div style="font-size: 26px; margin-bottom: 6px;">📭</div>
                                    <p style="margin: 0;">No notifications found.</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($studentNotifs as $notif): ?>
                                    <li>
                                        <a href="<?= e($notif['link']) ?>" class="notif-row <?= !empty($notif['unread']) ? 'unread' : '' ?>">
                                            <div class="notif-icon-circle <?= e($notif['type']) ?>">
                                                <?= $notif['icon'] ?>
                                            </div>
                                            <div class="notif-text">
                                                <div class="notif-title"><?= e($notif['title']) ?></div>
                                                <div class="notif-desc"><?= e($notif['desc']) ?></div>
                                                <div class="notif-meta-time">
                                                    <span>🕒</span> <?= e($notif['time']) ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($notif['unread'])): ?>
                                                <span class="notif-item-pill"></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                        <div class="notif-footer">
                            <a href="<?= APP_URL ?>/public/student/?view=receipts">
                                <span>View all Official Receipts</span> &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="user-chip-container" style="padding: 6px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <div class="user-chip-meta">
                        <span class="user-chip-name" style="font-weight: 700; color: #0f172a;"><?= e($studentName) ?></span>
                        <span class="user-chip-id" style="color: #64748b; font-size: 11.5px;">(<?= e($studentNum) ?>)</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Toast Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="app-toast success">
                <span>&#10003;</span> <?= e($successMsg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="app-toast error">
                <span>&#9888;</span> <?= e($errorMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <main class="content">

            <!-- ============================================== -->
            <!-- VIEW 0: STUDENT HOME / OVERVIEW               -->
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
                            <h1 class="portal-page-title">Welcome back, <?= e($studentName) ?>!</h1>
                            <p class="portal-page-sub">
                                Student ID: <strong><?= e($studentNum) ?></strong> &bull; 
                                Section: <strong><?= e($student['grade_level'] ?? 'BSCS 11A1') ?></strong> &bull; 
                                School Year: <strong><?= e($student['school_year'] ?? (date('Y') . '-' . (date('Y') + 1))) ?></strong>
                            </p>
                        </div>
                    </div>

                    <?php if ($primaryFee && $totalRemaining > 0): ?>
                        <button class="btn-pay-now-main" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Pay Tuition Now &rarr;
                        </button>
                    <?php elseif ($isPendingAssessment): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="pill-badge" style="background: #fef3c7; color: #b45309; font-weight: 700; padding: 7px 14px; border-radius: 9999px; font-size: 12px; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 7px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                Tuition Assessment Pending
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isPendingAssessment): ?>
                    <!-- Pending Assessment Onboarding Card & 3-Step Stepper -->
                    <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.05);">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                            <div style="max-width: 680px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <span class="pill-badge" style="background: #fef3c7; color: #b45309; font-weight: 700; font-size: 11.5px; padding: 3px 10px; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 5px;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                        Enrollment In Progress
                                    </span>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">Phase 2 of 3</span>
                                </div>
                                <h2 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">Tuition Assessment is Currently Under Evaluation</h2>
                                <p style="font-size: 13px; color: #475569; line-height: 1.6; margin: 0 0 12px;">
                                    Welcome to PayTrack! Your student account has been registered by the Administration. The <strong>Accounting Office</strong> is preparing your class curriculum, course units, and institutional fees. Once finalized, an official statement with fee breakdowns will be sent to your email (<strong><?= e($student['email'] ?? 'your email') ?></strong>) and your parent/guardian's email.
                                </p>
                            </div>
                            <div>
                                <button disabled class="btn-pay-now-main" style="opacity: 0.5; cursor: not-allowed; background: #64748b; box-shadow: none;" title="Payment will be activated once tuition assessment is published">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Payment Locked
                                </button>
                            </div>
                        </div>

                        <!-- 3-Step Interactive Progress Stepper -->
                        <div style="margin-top: 18px; padding-top: 18px; border-top: 1px dashed #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                            <!-- Step 1: Account Registered -->
                            <div style="display: flex; gap: 12px; align-items: flex-start; background: #ffffff; padding: 14px 16px; border-radius: 10px; border: 1px solid #bbf7d0;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                                    &#10003;
                                </div>
                                <div>
                                    <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;">1. Account Registered</div>
                                    <div style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 2px;">Completed &bull; Active Profile</div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Student login verified and active.</div>
                                </div>
                            </div>
                            <!-- Step 2: Tuition Assessment -->
                            <div style="display: flex; gap: 12px; align-items: flex-start; background: #fffbeb; padding: 14px 16px; border-radius: 10px; border: 1.5px solid #fde68a;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                                    ⏳
                                </div>
                                <div>
                                    <div style="font-size: 12.5px; font-weight: 700; color: #92400e;">2. Tuition Assessment</div>
                                    <div style="font-size: 11px; color: #d97706; font-weight: 600; margin-top: 2px;">In Progress &bull; Accounting</div>
                                    <div style="font-size: 11px; color: #78350f; margin-top: 3px;">Staff evaluating subject units &amp; fees.</div>
                                </div>
                            </div>
                            <!-- Step 3: Payment & Clearance -->
                            <div style="display: flex; gap: 12px; align-items: flex-start; background: #ffffff; padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0; opacity: 0.75;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                                    3
                                </div>
                                <div>
                                    <div style="font-size: 12.5px; font-weight: 700; color: #475569;">3. Payment &amp; Clearance</div>
                                    <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px;">Upcoming Next</div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">Online GCash/Maya &amp; instant receipts.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Helpful Footer Note -->
                        <div style="margin-top: 14px; font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                            <span>💡</span>
                            <span>Need urgent enrollment assistance? Visit the campus Accounting Office (Mon&ndash;Fri, 8AM&ndash;5PM) or email <strong>accounting@school.edu.ph</strong>.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 4 Quick Metric Summary Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Remaining Tuition Balance</div>
                        <?php if ($isPendingAssessment): ?>
                            <div style="font-size: 18px; font-weight: 800; color: #d97706;">Pending Assessment</div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                                Awaiting Accounting Office posting
                            </div>
                        <?php else: ?>
                            <div style="font-size: 24px; font-weight: 800; color: <?= $totalRemaining > 0 ? '#dc2626' : '#059669' ?>;"><?= peso($totalRemaining) ?></div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                                <?= $totalRemaining > 0 ? 'Due for active semester' : 'Tuition fully settled' ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Total Paid to Date</div>
                        <div style="font-size: 24px; font-weight: 800; color: #059669;"><?= peso($totalPaid) ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                            <?= $isPendingAssessment ? 'No assessment yet' : ($calcPct . '% of total assessment') ?>
                        </div>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Account Billing Status</div>
                        <div>
                            <?php if ($isPendingAssessment): ?>
                                <span class="pill-badge" style="font-size: 12px; padding: 4px 12px; background: #fef3c7; color: #b45309; font-weight: 700;">Evaluating &bull; In Progress</span>
                            <?php elseif ($totalRemaining <= 0): ?>
                                <span class="pill-badge green" style="font-size: 12px; padding: 4px 12px;">Fully Paid &#10003;</span>
                            <?php elseif ($totalPaid > 0): ?>
                                <span class="pill-badge blue" style="font-size: 12px; padding: 4px 12px; background: #e0f2fe; color: #0369a1;">Partial (<?= $calcPct ?>%)</span>
                            <?php else: ?>
                                <span class="pill-badge red" style="font-size: 12px; padding: 4px 12px; background: #fee2e2; color: #b91c1c;">Unpaid Assessment</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 6px;">
                            <?= $isPendingAssessment ? 'Enrollment registered' : 'Enrollment standing: Clear' ?>
                        </div>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Official Receipts</div>
                        <div style="font-size: 24px; font-weight: 800; color: #2563eb;"><?= count($payments) ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                            <a href="<?= APP_URL ?>/public/student/?view=receipts" style="color: #2563eb; text-decoration: none; font-weight: 600;">View Official Receipts &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- 2-Column Section: Recent Receipts & Quick Shortcuts -->
                <div class="dashboard-2col-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <!-- Left: Recent Payments Log -->
                    <div class="section-box" style="margin-bottom: 0;">
                        <div class="section-box-header">
                            <div class="section-header-left">
                                <div class="section-header-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <h2 class="section-box-title">Recent Payment Transactions</h2>
                            </div>
                            <a href="<?= APP_URL ?>/public/student/?view=history" style="font-size: 12px; font-weight: 600; color: #2563eb; text-decoration: none;">View Ledger &rarr;</a>
                        </div>

                        <div class="table-responsive">
                            <table class="styled-fintech-table">
                                <thead>
                                    <tr>
                                        <th>RECEIPT (OR#)</th>
                                        <th>AMOUNT</th>
                                        <th>METHOD</th>
                                        <th>DATE</th>
                                        <th style="text-align: right;">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentStudentPayments = array_slice($payments, 0, 3);
                                    if (empty($recentStudentPayments)): 
                                    ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 36px; color: #64748b;">
                                                No payment transactions recorded yet. Click "Pay Tuition Now" to make a payment.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentStudentPayments as $sp): ?>
                                            <tr>
                                                <td><code style="font-weight: 700; color: #0f172a;"><?= e($sp['or_number']) ?></code></td>
                                                <td><strong style="color: #059669;"><?= peso((float)$sp['amount']) ?></strong></td>
                                                <td><span class="badge-method-online"><?= strtoupper(e($sp['payment_method'])) ?></span></td>
                                                <td style="color: #475569; font-size: 12px;"><?= date('M d, Y', strtotime($sp['paid_at'])) ?></td>
                                                <td style="text-align: right;">
                                                    <button class="btn-view-receipt-outline" onclick="openOfficialReceiptModal('<?= e($sp['or_number']) ?>', '<?= peso((float)$sp['amount']) ?>', '<?= date('M d, Y h:i A', strtotime($sp['paid_at'])) ?>', '<?= strtoupper(e($sp['payment_method'])) ?>', '<?= e($sp['fee_desc']) ?>', '<?= e($studentName) ?>', '<?= e($studentNum) ?>', '<?= e($student['grade_level'] ?? 'BSCS 11A1') ?>')">
                                                        View OR
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Profile Info & Quick Actions -->
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 12px;">Student Portal Shortcuts</h4>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <a href="<?= APP_URL ?>/public/student/?view=fees" class="btn" style="justify-content: flex-start; gap: 8px; width: 100%;">
                                    <span>💳</span> My Tuition Breakdown &amp; Balance
                                </a>
                                <a href="<?= APP_URL ?>/public/student/?view=receipts" class="btn" style="justify-content: flex-start; gap: 8px; width: 100%;">
                                    <span>🧾</span> Official Receipts (OR) Directory
                                </a>
                                <a href="<?= APP_URL ?>/public/student/?view=history" class="btn" style="justify-content: flex-start; gap: 8px; width: 100%;">
                                    <span>📜</span> Complete Payment History Ledger
                                </a>
                                <a href="<?= APP_URL ?>/public/student/?view=refunds" class="btn" style="justify-content: flex-start; gap: 8px; width: 100%;">
                                    <span>↺</span> Refund Requests &amp; Approvals
                                    <?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?>
                                        <span class="badge" style="margin-left: auto; background: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 7px; border-radius: 99px;"><?= $pendingRefundsCount ?> Pending</span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>

                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: #0b3d2e; margin-bottom: 6px;">
                                <span>📢</span> Student Advisory
                            </div>
                            <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;">
                                Electronic Official Receipts are automatically timestamped and verified. Keep your official receipts for exam clearance and enrollment validation.
                            </p>
                        </div>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 1: MY TUITION & ASSESSMENT (FEES)         -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'fees'): ?>
                <!-- PAGE HEADER ROW -->
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path>
                                <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path>
                                <circle cx="18" cy="14" r="1"></circle>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">My Tuition &amp; Fees</h1>
                            <p class="portal-page-sub">View your tuition assessment breakdown and submit balance payments securely.</p>
                        </div>
                    </div>

                    <?php if ($primaryFee && $totalRemaining > 0): ?>
                        <button class="btn-pay-now-main" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Pay Tuition Now &rarr;
                        </button>
                    <?php endif; ?>
                </div>

                <!-- HERO 2-COLUMN GRID -->
                <div class="hero-dashboard-grid">
                    <!-- Left: Current Outstanding Balance -->
                    <div class="card-box">
                        <div class="card-eyebrow">Current Outstanding Balance</div>
                        <div class="card-term-sub">Term: <?= e($termDescription) ?></div>

                        <div class="balance-amount-row">
                            <?php if ($isPendingAssessment): ?>
                                <div class="balance-huge-red" style="font-size: 22px; color: #d97706;">Pending Assessment</div>
                                <button disabled class="btn-pay-pill-dark" style="opacity: 0.55; cursor: not-allowed; background: #64748b;" title="Payment will be enabled once tuition assessment is published">
                                    Payment Locked
                                </button>
                            <?php else: ?>
                                <div class="balance-huge-red"><?= peso($totalRemaining) ?></div>
                                <?php if ($primaryFee && $totalRemaining > 0): ?>
                                    <button class="btn-pay-pill-dark" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                            <line x1="1" y1="10" x2="23" y2="10"></line>
                                        </svg>
                                        Pay Tuition &gt;
                                    </button>
                                <?php else: ?>
                                    <span class="pill-badge green" style="font-size: 12px; padding: 5px 14px;">Fully Paid &#10003;</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="balance-meta-bullet">
                            <span class="dot" style="<?= $isPendingAssessment ? 'background: #f59e0b;' : '' ?>"></span>
                            <?php if ($isPendingAssessment): ?>
                                <span>Awaiting official tuition schedule and assessment from the Accounting Office</span>
                            <?php else: ?>
                                <span><strong><?= peso($totalPaid) ?></strong> paid of <strong><?= peso($totalFees) ?></strong> total assessment (<?= $calcPct ?>%)</span>
                            <?php endif; ?>
                        </div>

                        <div class="progress-track-wrapper">
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill-green" style="width: <?= $calcPct ?>%;"></div>
                            </div>
                            <span class="progress-pct-label"><?= $isPendingAssessment ? 'Pending' : ($calcPct . '%') ?></span>
                        </div>
                    </div>

                    <!-- Right: Motivation Banner Card -->
                    <div class="motivation-hero-card">
                        <div class="motivation-text-col">
                            <div class="motivation-quote-text">
                                &ldquo; Small steps today,<br>big dreams tomorrow. &rdquo;
                            </div>
                            <div class="quote-accent-bar"></div>
                        </div>

                        <div class="motivation-gfx-col">
                            <div class="floating-grad-badge">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                                </svg>
                            </div>

                            <svg width="115" height="75" viewBox="0 0 115 75" fill="none" style="display: block;">
                                <rect x="5" y="52" width="105" height="16" rx="3" fill="#1e3a8a"/>
                                <rect x="3" y="52" width="8" height="16" rx="2" fill="#0f172a"/>
                                <path d="M108 55H15v10h93c2 0 3-1 3-3v-4c0-2-1-3-3-3z" fill="#f8fafc"/>
                                <rect x="22" y="58" width="26" height="2.5" rx="1.2" fill="#93c5fd"/>
                                
                                <rect x="16" y="34" width="94" height="15" rx="3" fill="#0284c7"/>
                                <rect x="13" y="34" width="8" height="15" rx="2" fill="#0369a1"/>
                                <path d="M108 37H24v9h84c2 0 3-1 3-2.5v-4c0-1.5-1-2.5-3-2.5z" fill="#f8fafc"/>
                                <rect x="32" y="39" width="22" height="2" rx="1" fill="#bae6fd"/>

                                <rect x="26" y="17" width="84" height="14" rx="3" fill="#d97706"/>
                                <rect x="23" y="17" width="8" height="14" rx="2" fill="#b45309"/>
                                <path d="M108 20H34v8h74c2 0 3-1 3-2v-4c0-1-1-2-3-2z" fill="#f8fafc"/>
                                <rect x="42" y="22" width="18" height="2" rx="1" fill="#fde68a"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- TUITION FEE ASSESSMENT BREAKDOWN SECTION -->
                <div class="section-box">
                    <div class="section-box-header">
                        <div class="section-header-left">
                            <div class="section-header-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <h2 class="section-box-title">Tuition Fee Assessment Breakdown</h2>
                        </div>
                        <span class="section-box-meta"><?= e($termDescription) ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="styled-fintech-table">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                                <line x1="9" y1="21" x2="9" y2="9"></line>
                                            </svg>
                                            Fee Aspect / Category
                                        </span>
                                    </th>
                                    <th style="width: 35%;">Classification</th>
                                    <th style="text-align: right; width: 20%;">Assessed Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($isPendingAssessment || empty($breakdownItems)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 44px 20px; background: #fafafa;">
                                            <div style="max-width: 520px; margin: 0 auto; text-align: center;">
                                                <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 22px;">
                                                    ⏳
                                                </div>
                                                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">Tuition Assessment in Preparation</h3>
                                                <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin: 0 0 16px;">
                                                    The Accounting Office is currently processing your academic program and calculating your subject units, laboratory access, and institutional fees.
                                                </p>
                                                <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; font-size: 12px; color: #334155;">
                                                    <span style="color: #2563eb;">📧</span>
                                                    <span>Official breakdown will be sent to: <strong><?= e($student['email'] ?? 'Registered Student Email') ?></strong></span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($breakdownItems as $item): ?>
                                        <?php
                                            $name = $item['category_name'];
                                            $isSubj = (stripos($name, 'Subject') !== false || stripos($name, 'Tuition') !== false);
                                            $isLms  = (stripos($name, 'LMS') !== false || stripos($name, 'E-Learning') !== false);
                                            $isLab  = (stripos($name, 'Lab') !== false);
                                            $isLib  = (stripos($name, 'Library') !== false);
                                            $isMed  = (stripos($name, 'Medical') !== false || stripos($name, 'Clinic') !== false);
                                            $isAth  = (stripos($name, 'Athletic') !== false || stripos($name, 'Activity') !== false);
                                            $isReg  = (stripos($name, 'Registration') !== false || stripos($name, 'Matriculation') !== false);
                                            
                                            $color = 'blue';
                                            $badgeText = 'Institutional Standard Fee';
                                            if ($isSubj) {
                                                $color = 'green';
                                                $badgeText = 'Course Units / Academic';
                                            } elseif ($isLab || $isLms) {
                                                $color = 'blue';
                                                $badgeText = 'Technology & Lab Facilities';
                                            } elseif ($isMed) {
                                                $color = 'green';
                                                $badgeText = 'Student Health & Welfare';
                                            } elseif ($isAth) {
                                                $color = 'purple';
                                                $badgeText = 'Co-Curricular & Sports';
                                            } elseif ($isReg || $isLib) {
                                                $color = 'purple';
                                                $badgeText = 'Academic Support';
                                            } else {
                                                $color = 'purple';
                                                $badgeText = 'Institutional Standard Fee';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="aspect-cell">
                                                    <div class="aspect-icon-badge <?= $color ?>">
                                                        <?php if ($isSubj): ?>
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                                                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                                                            </svg>
                                                        <?php elseif ($isLms || $isLab): ?>
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                                                <line x1="12" y1="17" x2="12" y2="21"></line>
                                                            </svg>
                                                        <?php elseif ($isMed): ?>
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                                            </svg>
                                                        <?php else: ?>
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                                            </svg>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span><?= e($name) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="pill-badge <?= $color ?>">
                                                    <?= $badgeText ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 800; color: #0f172a;">
                                                <?= peso((float)$item['amount']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Total Row -->
                                    <tr class="table-total-row">
                                        <td colspan="2">
                                            <div class="aspect-cell">
                                                <div class="aspect-icon-badge blue">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                                                        <line x1="8" y1="6" x2="16" y2="6"></line>
                                                        <line x1="16" y1="14" x2="16" y2="18"></line>
                                                        <path d="M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M8 18h.01M12 18h.01"></path>
                                                    </svg>
                                                </div>
                                                <span>Total Institutional Assessment</span>
                                            </div>
                                        </td>
                                        <td style="text-align: right; font-size: 15px; font-weight: 900; color: #0f172a;">
                                            <?= peso($totalFees) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Green Information Notice -->
                    <div class="green-notice-callout">
                        <div class="green-notice-circle">i</div>
                        <div class="green-notice-body">
                            <strong class="notice-title">Notice regarding fee aspects:</strong>
                            The breakdown above reflects your school fee assessment allocation. When you make a payment, it is directly deducted from your <strong>Total Remaining Balance</strong>.
                        </div>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 2: DEDICATED PAYMENT HISTORY PAGE         -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'history'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #ecfdf5; color: #059669;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Payment History &amp; Official Receipts</h1>
                            <p class="portal-page-sub">Review all your recorded payment transactions and download or view electronic official receipts.</p>
                        </div>
                    </div>

                    <?php if ($primaryFee && $totalRemaining > 0): ?>
                        <button class="btn-pay-now-main" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Pay Tuition Now &rarr;
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Metrics Summary Cards -->
                <div class="metrics-grid-3">
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Total Amount Paid</div>
                        <div class="metric-mini-val" style="color: #059669;"><?= peso($totalPaid) ?></div>
                    </div>
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Transactions Recorded</div>
                        <div class="metric-mini-val" style="color: #1d4ed8;"><?= count($payments) ?></div>
                    </div>
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Outstanding Balance</div>
                        <div class="metric-mini-val" style="color: <?= $totalRemaining > 0 ? '#dc2626' : '#059669' ?>;">
                            <?= peso($totalRemaining) ?>
                        </div>
                    </div>
                </div>

                <!-- Payments Table Section -->
                <div class="section-box">
                    <div class="section-box-header">
                        <div class="section-header-left">
                            <div class="section-header-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <h2 class="section-box-title">Transactions Log</h2>
                        </div>
                        <span class="section-box-meta"><?= count($payments) ?> Transactions Found</span>
                    </div>

                    <div class="table-responsive">
                        <table class="styled-fintech-table" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>OFFICIAL RECEIPT (OR#)</th>
                                    <th>ASSESSMENT TERM</th>
                                    <th>SUBJECTS</th>
                                    <th>PAYMENT METHOD</th>
                                    <th>AMOUNT PAID</th>
                                    <th>DATE &amp; TIME</th>
                                    <th style="text-align: right;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 48px; color: #64748b;">
                                            No payment transactions recorded yet. Click "Pay Tuition Now" to submit your first installment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p): ?>
                                        <?php
                                            $paymentSubjects = json_decode((string)($p['enrolled_subjects_snapshot'] ?? ''), true);
                                            $currentSubjects = json_decode((string)($p['current_enrolled_subjects'] ?? ''), true);
                                            $currentCodes = is_array($currentSubjects) ? array_map(static fn($subject) => strtoupper(trim((string)($subject['code'] ?? ''))), $currentSubjects) : [];
                                        ?>
                                        <tr>
                                            <td><code style="font-weight: 700; color: #0f172a; font-size: 13px;"><?= e($p['or_number']) ?></code></td>
                                            <td style="color: #475569;"><?= e($p['fee_desc']) ?></td>
                                            <td style="font-size:12px;">
                                                <?php if (is_array($paymentSubjects) && $paymentSubjects): ?>
                                                    <?php foreach ($paymentSubjects as $subject): ?>
                                                        <?php $code = trim((string)($subject['code'] ?? '')); $removed = is_array($currentSubjects) && !in_array(strtoupper($code), $currentCodes, true); ?>
                                                        <span style="display:inline-block;margin:1px 4px 1px 0;<?= $removed ? 'color:#dc2626;font-weight:700;' : 'color:#475569;' ?>" title="<?= $removed ? 'Removed from current assessment' : 'Enrolled subject' ?>"><?= e($code ?: ($subject['description'] ?? 'Subject')) ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-method-online">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                                                        <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                                                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                                                        <line x1="12" y1="20" x2="12.01" y2="20"></line>
                                                    </svg>
                                                    <?= strtoupper(e($p['payment_method'])) ?>
                                                </span>
                                            </td>
                                            <td><strong style="color: #059669; font-weight: 800; font-size: 13.5px;"><?= peso((float)$p['amount']) ?></strong></td>
                                            <td style="color: #475569; font-size: 12.5px;"><?= date('M d, Y h:i A', strtotime($p['paid_at'])) ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="<?= APP_URL ?>/public/student/?view=receipt&amp;or=<?= urlencode($p['or_number']) ?>" target="_blank" class="btn-view-receipt-outline" style="text-decoration:none;">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    View OR
                                                </a>
                                                <a href="<?= APP_URL ?>/public/student/?view=receipt&or=<?= urlencode($p['or_number']) ?>" target="_blank" class="btn-view-receipt-outline" style="background: #0b3d2e; color: #ffffff; border-color: #0b3d2e; text-decoration: none;" title="Open and print official receipt">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                                    Print
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 2.5: DEDICATED OFFICIAL RECEIPTS PAGE     -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'receipts'): ?>
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #eff6ff; color: #2563eb;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Official Electronic Receipts (OR)</h1>
                            <p class="portal-page-sub">Verified institutional payment vouchers generated upon transaction confirmation.</p>
                        </div>
                    </div>

                    <?php if ($primaryFee && $totalRemaining > 0): ?>
                        <button class="btn-pay-now-main" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Pay Tuition Now &rarr;
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Metrics Row -->
                <div class="metrics-grid-3">
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Total Receipts Issued</div>
                        <div class="metric-mini-val" style="color: #2563eb;"><?= count($payments) ?></div>
                    </div>
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Total Verified Payments</div>
                        <div class="metric-mini-val" style="color: #059669;"><?= peso($totalPaid) ?></div>
                    </div>
                    <div class="metric-mini-card">
                        <div class="metric-mini-label">Official Status</div>
                        <div class="metric-mini-val" style="color: #0b3d2e; font-size: 18px;">
                            Clearance Valid
                        </div>
                    </div>
                </div>

                <!-- Receipts Grid / Card Directory -->
                <div class="section-box">
                    <div class="section-box-header">
                        <div class="section-header-left">
                            <div class="section-header-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <h2 class="section-box-title">Official Receipt Records</h2>
                        </div>
                        <span class="section-box-meta"><?= count($payments) ?> Official Receipts Available</span>
                    </div>

                    <?php if (empty($payments)): ?>
                        <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                            <div style="font-size: 36px; margin-bottom: 12px;">🧾</div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">No Official Receipts Available</h3>
                            <p style="font-size: 13px; max-width: 440px; margin: 0 auto 16px;">
                                You have not made any tuition payments yet. Once a payment is submitted and confirmed, your official electronic receipt will appear here for download and printing.
                            </p>
                            <?php if ($primaryFee && $totalRemaining > 0): ?>
                                <button class="btn-pay-now-main" onclick="openPaymentModal(<?= $primaryFee['id'] ?>, '<?= e($termDescription) ?>', <?= $totalRemaining ?>)">
                                    Pay Tuition Now &rarr;
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="receipt-cards-grid" style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px;">
                            <?php foreach ($payments as $p): ?>
                                <div class="receipt-card-item" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.03); transition: all 0.2s ease;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 10px;">
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Official Receipt No.</div>
                                            <div style="font-size: 16px; font-weight: 800; color: #0f172a; font-family: monospace;"><?= e($p['or_number']) ?></div>
                                        </div>
                                        <span class="badge-pill paid" style="font-size: 10.5px; padding: 3px 8px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                                            &#10003; VERIFIED
                                        </span>
                                    </div>

                                    <div style="font-size: 12.5px; line-height: 1.8; color: #334155; margin-bottom: 14px;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #64748b;">Assessment:</span>
                                            <strong style="text-align: right; max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= e($p['fee_desc']) ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #64748b;">Amount Paid:</span>
                                            <strong style="color: #059669; font-size: 14px;"><?= peso((float)$p['amount']) ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #64748b;">Payment Method:</span>
                                            <span style="font-weight: 600; text-transform: uppercase;"><?= e($p['payment_method']) ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #64748b;">Date Issued:</span>
                                            <span><?= date('M d, Y h:i A', strtotime($p['paid_at'])) ?></span>
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn-view-receipt-outline" style="flex: 1; justify-content: center; padding: 8px;"
                                            onclick="openOfficialReceiptModal('<?= e($p['or_number']) ?>', '<?= peso((float)$p['amount']) ?>', '<?= date('M d, Y h:i A', strtotime($p['paid_at'])) ?>', '<?= strtoupper(e($p['payment_method'])) ?>', '<?= e($p['fee_desc']) ?>', '<?= e($studentName) ?>', '<?= e($studentNum) ?>', '<?= e($student['grade_level'] ?? 'BSCS 11A1') ?>')">
                                            <span>👁</span> View Details
                                        </button>
                                        <a href="<?= APP_URL ?>/public/student/?view=receipt&or=<?= urlencode($p['or_number']) ?>" target="_blank" class="btn" style="flex: 1; justify-content: center; padding: 8px; background: #0b3d2e; color: #ffffff; border: 1px solid #0b3d2e; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                            <span>🖨</span> Print Voucher
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- ============================================== -->
            <!-- VIEW 5: REFUND REQUESTS & ADJUSTMENTS          -->
            <!-- ============================================== -->
            <?php elseif ($currentView === 'refunds'): ?>
                <?php
                    $pendingRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'pending');
                    $approvedRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'approved');
                    $rejectedRefundsList = array_filter($refunds, fn($r) => $r['status'] === 'rejected');
                    $primaryMaxRefundable = $primaryFee ? max(0.0, (float)$primaryFee['amount_paid'] - Refund::getPendingAmountForFee((int)$primaryFee['id'])) : 0.0;
                ?>
                <!-- PAGE HEADER ROW -->
                <div class="portal-header-row">
                    <div class="portal-title-flex">
                        <div class="wallet-icon-box" style="background: #f0fdf4; color: #16a34a;">
                            <span style="font-size: 22px;">↺</span>
                        </div>
                        <div>
                            <h1 class="portal-page-title">Tuition Refund Requests</h1>
                            <p class="portal-page-sub">Submit refund requests with a valid reason and monitor Accounting Office assessments</p>
                        </div>
                    </div>

                    <div>
                        <button type="button" class="btn-pay-now-main" onclick="openRequestRefundModal()" style="background: #0b3d2e;">
                            <span style="font-size: 16px;">+</span> File Refund Request
                        </button>
                    </div>
                </div>

                <!-- KPI STATS CARDS -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Approved Refunds</div>
                        <div style="font-size: 24px; font-weight: 800; color: #15803d; margin-top: 4px;"><?= peso($totalRefunded) ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><?= count($approvedRefundsList) ?> approved adjustment(s)</div>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Pending Assessment</div>
                        <div style="font-size: 24px; font-weight: 800; color: #d97706; margin-top: 4px;"><?= count($pendingRefundsList) ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Under Accounting review</div>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Available Paid to Refund</div>
                        <div style="font-size: 24px; font-weight: 800; color: #2563eb; margin-top: 4px;"><?= peso($primaryMaxRefundable) ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Eligible for refund filing</div>
                    </div>
                </div>

                <!-- REFUND POLICY CALLOUT -->
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="font-size: 22px; line-height: 1;">ℹ️</div>
                    <div style="font-size: 13px; color: #1e3a8a; line-height: 1.55;">
                        <strong style="color: #1e40af;">Refund Verification Process:</strong> All refund requests require an official, valid reason (e.g., dropped course units, overpayment, duplicate payment, or enrollment withdrawal). The Accounting Office reviews and assesses each submission. Once approved, the amount is deducted from your payments, your tuition balance reflects the adjustment, and an official confirmation email is sent to you and your parents.
                    </div>
                </div>

                <!-- REFUND REQUESTS TABLE CONTAINER -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">Refund History &amp; Request Tracking</h2>
                            <p style="font-size: 12.5px; color: #64748b; margin: 3px 0 0;">View the real-time review status and Accounting notes for each filed request</p>
                        </div>
                    </div>

                    <?php if (empty($refunds)): ?>
                        <div style="padding: 48px 20px; text-align: center; color: #64748b;">
                            <div style="font-size: 40px; margin-bottom: 12px; opacity: 0.6;">↺</div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">No Refund Requests Found</h3>
                            <p style="font-size: 13px; max-width: 480px; margin: 0 auto 18px; line-height: 1.5;">
                                You have not submitted any refund requests yet. If you have made a payment and need an adjustment with a valid reason, click the button below.
                            </p>
                            <button type="button" class="btn" onclick="openRequestRefundModal()" style="background: #0b3d2e; color: #ffffff; font-weight: 700; padding: 9px 20px;">
                                Submit Your First Refund Request
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 11.5px; text-transform: uppercase;">
                                        <th style="padding: 10px 12px;">Ref. Number</th>
                                        <th style="padding: 10px 12px;">Assessment / Receipt</th>
                                        <th style="padding: 10px 12px;">Amount</th>
                                        <th style="padding: 10px 12px;">Reason Provided</th>
                                        <th style="padding: 10px 12px;">Status</th>
                                        <th style="padding: 10px 12px;">Accounting Assessment</th>
                                        <th style="padding: 10px 12px;">Date Filed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refunds as $ref): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; vertical-align: top;">
                                            <td style="padding: 12px; font-family: monospace; font-weight: 700; color: #0b3d2e;">
                                                <?= e($ref['refund_number']) ?>
                                            </td>
                                            <td style="padding: 12px;">
                                                <div style="font-weight: 600; color: #0f172a;"><?= e($ref['fee_desc']) ?></div>
                                                <?php if (!empty($ref['or_number'])): ?>
                                                    <div style="font-size: 11.5px; color: #64748b;">OR: <span style="font-family: monospace; font-weight: 600;"><?= e($ref['or_number']) ?></span></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; font-weight: 800; font-size: 14px; color: #0f172a;">
                                                <?= peso((float)$ref['amount']) ?>
                                            </td>
                                            <td style="padding: 12px; max-width: 260px;">
                                                <div style="background: #f8fafc; border-left: 3px solid #cbd5e1; padding: 6px 10px; border-radius: 4px; font-size: 12px; color: #334155; line-height: 1.45; font-style: italic;">
                                                    "<?= e($ref['reason']) ?>"
                                                </div>
                                            </td>
                                            <td style="padding: 12px;">
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
                                            <td style="padding: 12px; max-width: 220px;">
                                                <?php if ($ref['status'] === 'approved'): ?>
                                                    <div style="font-size: 12px; color: #166534;">
                                                        <strong>Deducted from balance</strong>
                                                        <?php if (!empty($ref['review_notes'])): ?>
                                                            <div style="font-size: 11.5px; color: #4b5563; margin-top: 3px;">Note: <?= e($ref['review_notes']) ?></div>
                                                        <?php endif; ?>
                                                        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">Assessed: <?= date('M d, Y', strtotime($ref['reviewed_at'])) ?></div>
                                                    </div>
                                                <?php elseif ($ref['status'] === 'rejected'): ?>
                                                    <div style="font-size: 12px; color: #991b1b;">
                                                        <strong>Declined by Accounting</strong>
                                                        <?php if (!empty($ref['rejection_reason'])): ?>
                                                            <div style="font-size: 11.5px; color: #7f1d1d; margin-top: 3px; background: #fff1f2; padding: 4px 8px; border-radius: 4px;">
                                                                <?= e($ref['rejection_reason']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">Reviewed: <?= date('M d, Y', strtotime($ref['reviewed_at'])) ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="font-size: 12px; color: #64748b; font-style: italic;">
                                                        Awaiting assessment by Accounting Staff
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; font-size: 12px; color: #64748b; white-space: nowrap;">
                                                <?= date('M d, Y h:i A', strtotime($ref['requested_at'])) ?>
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

<!-- ========================================== -->
<!-- MODAL: PAY TUITION MODAL                   -->
<!-- ========================================== -->
<div class="modal-backdrop" id="paymentInputModal">
    <div class="modal-window" style="max-width: 440px;">
        <button class="modal-close-x" id="btnClosePaymentInputModal">&times;</button>
        <h2 class="modal-header-title">Pay Tuition Fee</h2>

        <form method="POST" action="<?= APP_URL ?>/public/student/?view=<?= e($currentView) ?>" id="paymentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="pay_tuition">
            <input type="hidden" name="fee_id" id="modalFeeId">

            <div class="form-group">
                <label class="form-label">Tuition Assessment</label>
                <div id="modalFeeDesc" style="font-weight: 600; padding: 8px 12px; background: #f4f4f5; border-radius: 6px; font-size: 13px;"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Outstanding Balance</label>
                <div id="modalFeeBalance" style="font-size: 18px; font-weight: 700; color: #dc2626;"></div>
            </div>

            <!-- Quick Amount Select Buttons -->
            <div class="form-group">
                <label class="form-label">Quick Select Amount</label>
                <div class="quick-pay-btn-group" style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn" style="padding: 5px 12px; font-size: 11.5px;" id="pillFullPay">Full Balance</button>
                    <button type="button" class="btn" style="padding: 5px 12px; font-size: 11.5px;" id="pillHalfPay">50% Balance</button>
                    <button type="button" class="btn" style="padding: 5px 12px; font-size: 11.5px;" id="pill1k">₱1,000</button>
                    <button type="button" class="btn" style="padding: 5px 12px; font-size: 11.5px;" id="pill5k">₱5,000</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="payAmount">Payment Amount (₱) *</label>
                <input type="number" step="0.01" min="1" class="form-control" name="amount" id="payAmount" required placeholder="Enter amount to pay">
            </div>

            <div class="form-group">
                <label class="form-label">Payment Channel</label>
                <select class="form-control" name="payment_method" required>
                    <option value="online">Online Payment Portal</option>
                    <option value="gcash">GCash</option>
                    <option value="maya">Maya</option>
                    <option value="bank_transfer">Bank Transfer (BDO / BPI / UnionBank)</option>
                    <option value="card">Debit / Credit Card</option>
                </select>
            </div>

            <div style="margin-top: 18px;">
                <button type="submit" class="btn dark" id="btnSubmitPayment" style="width: 100%; justify-content: center; padding: 11px;">
                    Confirm &amp; Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- FULLSCREEN EMAIL & PAYMENT LOADING OVERLAY -->
<!-- ========================================== -->
<div class="paytrack-loading-backdrop" id="paymentLoadingOverlay">
    <div class="paytrack-loading-card">
        <div class="paytrack-spinner-ring"></div>
        <div class="paytrack-loading-title">Processing Payment...</div>
        <p class="paytrack-loading-desc">
            Please wait while your payment is securely verified and an official electronic receipt is generated and emailed to you and your parents.
        </p>
        <div class="paytrack-loading-bar-wrapper">
            <div class="paytrack-loading-bar-fill"></div>
        </div>
        <small style="color: #94a3b8; font-size: 11px;">Do not close or refresh this page.</small>
    </div>
</div>

<!-- ========================================== -->
<!-- FULLSCREEN REFUND PROCESSING LOADING OVERLAY -->
<!-- ========================================== -->
<div class="paytrack-loading-backdrop" id="refundLoadingOverlay">
    <div class="paytrack-loading-card">
        <div class="paytrack-spinner-ring"></div>
        <div class="paytrack-loading-title">Submitting Refund Request...</div>
        <p class="paytrack-loading-desc">
            Please wait while your refund request is recorded and an official notification is dispatched to the Accounting Office.
        </p>
        <div class="paytrack-loading-bar-wrapper">
            <div class="paytrack-loading-bar-fill"></div>
        </div>
        <small style="color: #94a3b8; font-size: 11px;">Do not close or refresh this page.</small>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: NATIONAL COLLEGE OF SCIENCE AND TECHNOLOGY   -->
<!--        OFFICIAL ELECTRONIC RECEIPT VOUCHER           -->
<!-- ==================================================== -->
<div class="modal-backdrop" id="officialReceiptModal">
    <div class="modal-window" style="max-width: 900px; padding: 0; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <!-- Top Modal Action Bar (Hidden on Print) -->
        <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; background: #0b3d2e; color: #ffffff; padding: 12px 20px;">
            <div style="font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <span>🧾</span> NCST Certificate of Registration (COR) &amp; Electronic Receipt
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" onclick="window.printReceiptModal()" style="background: #ffffff; color: #0b3d2e; border: none; padding: 7px 16px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print / Save PDF
                </button>
                <button type="button" onclick="closeReceiptModal()" style="background: rgba(255,255,255,0.2); color: #fff; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
        </div>

        <!-- Printable Receipt Content Area -->
        <div id="ncstPrintableReceipt" style="padding: 24px 28px; background: #ffffff; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; max-height: 85vh; overflow-y: auto;">
            <!-- Header -->
            <div style="text-align: center; border-bottom: 2px solid #000000; padding-bottom: 8px; margin-bottom: 12px;">
                <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #4b5563;">Republic of the Philippines</div>
                <h1 style="font-size: 18px; font-weight: 900; color: #0b3d2e; margin: 2px 0; letter-spacing: 0.5px; text-transform: uppercase;">
                    National College of Science and Technology
                </h1>
                <p style="font-size: 11px; color: #374151; margin: 0 0 6px;">
                    Emilio Aguinaldo Highway, Dasmariñas City, Cavite 4114 &bull; Tel. No. (046) 416-0166
                </p>
                <div style="display: inline-block; font-size: 12.5px; font-weight: 800; color: #000000; letter-spacing: 1.5px; text-transform: uppercase; border: 1.5px solid #000000; padding: 3px 18px; background: #f8fafc;">
                    CERTIFICATE OF REGISTRATION / ASSESSMENT OF FEES
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 6px; font-weight: 600;">
                    <span>REFERENCE / OR NO: <strong id="rcptOrNumber" style="font-family: monospace; font-size: 12px; color: #0b3d2e;">OR-2024-00001</strong></span>
                    <span>PRINT DATE: <strong id="rcptPrintDate"><?= date('m/d/Y h:i A') ?></strong></span>
                </div>
            </div>

            <!-- Student Info Grid -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; border: 1.5px solid #000000; margin-bottom: 12px; background: #ffffff;">
                <div style="padding: 4px 8px; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Student Name</div>
                    <div id="rcptStudentName" style="font-size: 11.5px; font-weight: 800; color: #000000;"><?= e($studentName) ?></div>
                </div>
                <div style="padding: 4px 8px; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Student No</div>
                    <div id="rcptStudentId" style="font-size: 11.5px; font-weight: 800; color: #000000; font-family: monospace;"><?= e($studentNum) ?></div>
                </div>
                <div style="padding: 4px 8px; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Course / Program</div>
                    <div id="rcptCourse" style="font-size: 11.5px; font-weight: 800; color: #000000;">BSIT</div>
                </div>

                <div style="padding: 4px 8px; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Year Level</div>
                    <div id="rcptYearLevel" style="font-size: 11.5px; font-weight: 800; color: #000000;">4th Year</div>
                </div>
                <div style="padding: 4px 8px; border-right: 1px solid #000000; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Section Code</div>
                    <div id="rcptCourseSection" style="font-size: 11.5px; font-weight: 800; color: #000000;"><?= e($student['grade_level'] ?? '41A2') ?></div>
                </div>
                <div style="padding: 4px 8px; border-bottom: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">School Year &amp; Semester</div>
                    <div id="rcptSchoolYearSem" style="font-size: 11px; font-weight: 700; color: #000000;">S.Y. 2026-2027 &bull; 1st Semester</div>
                </div>

                <div style="padding: 4px 8px; border-right: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Student Email</div>
                    <div id="rcptStudentEmail" style="font-size: 11px; font-weight: 700; color: #000000;"><?= e($student['email'] ?? '—') ?></div>
                </div>
                <div style="padding: 4px 8px; border-right: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Parent / Guardian</div>
                    <div id="rcptParentName" style="font-size: 11px; font-weight: 700; color: #000000;"><?= e($student['parent_name'] ?? '—') ?></div>
                </div>
                <div style="padding: 4px 8px;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #4b5563; text-transform: uppercase;">Parent Contact No</div>
                    <div id="rcptContact" style="font-size: 11px; font-weight: 700; color: #000000;"><?= e($student['contact_number'] ?? $student['contact'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Specified Subjects Schedule -->
            <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; background: #0b3d2e; color: #ffffff; padding: 4px 8px; margin-bottom: 2px; letter-spacing: 0.5px;">
                SCHEDULE OF SPECIFIED ENROLLED SUBJECTS
            </div>
            <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000000; margin-bottom: 12px; font-size: 10.5px;">
                <thead>
                    <tr style="background: #e2e8f0; color: #000000; font-size: 9.5px; text-transform: uppercase;">
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 10%;">Subject Code</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 32%; text-align: left;">Descriptive Title</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 6%; text-align: center;">Units</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 8%; text-align: center;">Type</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 9%; text-align: center;">Days</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 8%; text-align: center;">Start</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 8%; text-align: center;">End</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 7%; text-align: center;">Section</th>
                        <th style="padding: 5px 6px; border: 1px solid #000000; width: 12%; text-align: center;">Room</th>
                    </tr>
                </thead>
                <tbody id="rcptSubjectsTbody">
                    <!-- Populated dynamically by JS -->
                </tbody>
                <tfoot>
                    <tr style="font-weight: 800; background: #f1f5f9; border-top: 1.5px solid #000000;">
                        <td colspan="2" style="text-align: right; padding: 4px 8px; border: 1px solid #000000;">TOTAL NUMBER OF ACADEMIC UNITS:</td>
                        <td id="rcptTotalUnits" style="text-align: center; padding: 4px 6px; border: 1px solid #000000; font-size: 12px; color: #0b3d2e; font-weight: 900;">13</td>
                        <td colspan="6" style="text-align: left; padding: 4px 8px; border: 1px solid #000000; font-size: 10px; color: #4b5563;">
                            Regular Academic Load &bull; Certified by NCST Registrar
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Bottom Grid: Assessment & Electronic Payment Details -->
            <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 12px; margin-bottom: 12px;">
                <!-- Fee Assessment Breakdown -->
                <div style="border: 1.5px solid #000000;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; background: #0b3d2e; color: #ffffff; padding: 4px 8px;">
                        ASSESSMENT OF TUITION &amp; INSTITUTIONAL FEES
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;">
                        <thead>
                            <tr style="background: #e2e8f0; border-bottom: 1px solid #000000; font-size: 9.5px; text-transform: uppercase;">
                                <th style="padding: 4px 8px; text-align: left;">Particulars / Fee Aspect</th>
                                <th style="padding: 4px 8px; text-align: right;">Assessed Amount</th>
                            </tr>
                        </thead>
                        <tbody id="rcptFeesTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 1.5px solid #000000; border-bottom: 1.5px solid #000000; background: #f8fafc; font-weight: 800; font-size: 11px;">
                                <td style="padding: 4px 8px;">TOTAL CASH TUITION &amp; FEES:</td>
                                <td id="rcptCashTotal" style="padding: 4px 8px; text-align: right; color: #0b3d2e; font-size: 12px; font-weight: 900;">₱0.00</td>
                            </tr>
                            <tr style="font-size: 10px; color: #4b5563;">
                                <td style="padding: 3px 8px;">Installment Interest / Surcharge:</td>
                                <td id="rcptInstallmentCharge" style="padding: 3px 8px; text-align: right; font-weight: 600;">₱0.00</td>
                            </tr>
                            <tr style="background: #fdf2f8; font-weight: 800; color: #831843;">
                                <td style="padding: 4px 8px;">TOTAL INSTALLMENT TUITION &amp; FEES:</td>
                                <td id="rcptInstallmentTotal" style="padding: 4px 8px; text-align: right;">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Transaction Receipt -->
                <div style="border: 1.5px solid #000000; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="padding: 10px 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #000000; padding-bottom: 4px; margin-bottom: 8px;">
                            <span style="font-weight: 800; text-transform: uppercase; font-size: 10.5px;">PAYMENT TRANSACTION RECEIPT</span>
                            <span id="rcptOrNumberSide" style="font-family: monospace; font-size: 12px; font-weight: 800; color: #0b3d2e;">OR-2024-00001</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px;">
                            <span style="color: #4b5563;">Payment Channel:</span>
                            <strong id="rcptPaymentMethod" style="color: #0b3d2e;">ONLINE</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px;">
                            <span style="color: #4b5563;">Date &amp; Time Credited:</span>
                            <strong id="rcptDate">—</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px;">
                            <span style="color: #4b5563;">Particulars:</span>
                            <span id="rcptFeeDesc">Tuition Fee Assessment Installment</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; border-top: 1px dashed #cbd5e1; margin-top: 4px;">
                            <span style="color: #4b5563;">Remaining Balance:</span>
                            <strong id="rcptRemainingBalance" style="color: #b91c1c; font-size: 11.5px;">₱0.00</strong>
                        </div>

                        <div style="background: #ecfdf5; border: 1.5px solid #059669; padding: 8px; text-align: center; margin-top: 8px; border-radius: 4px;">
                            <div style="font-size: 9.5px; font-weight: 800; text-transform: uppercase; color: #065f46;">Amount Paid &amp; Credited</div>
                            <div id="rcptAmount" style="font-size: 20px; font-weight: 900; color: #047857; margin: 2px 0;">₱0.00</div>
                            <div style="font-size: 9.5px; color: #065f46; font-weight: 600;">&#10003; PayTrack Electronic Payment Verified</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-top: 1px solid #000000; padding: 6px 10px; font-size: 9.5px; color: #4b5563; text-align: center;">
                        Certified Official Electronic Record &bull; Valid for Exam Clearance
                    </div>
                </div>
            </div>

            <!-- Signatures Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 10px; padding-top: 8px; border-top: 1.5px solid #000000; font-size: 10px;">
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #000000; height: 26px; margin-bottom: 4px;"></div>
                    <div id="rcptSigStudent" style="font-weight: 800; text-transform: uppercase;"><?= e($studentName) ?></div>
                    <div>Student / Guardian Signature</div>
                </div>
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #000000; height: 26px; margin-bottom: 4px;"></div>
                    <div style="font-weight: 800; text-transform: uppercase;">OFFICE OF THE REGISTRAR</div>
                    <div>Validated &amp; Enrolled</div>
                </div>
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #000000; height: 26px; margin-bottom: 4px;"></div>
                    <div style="font-weight: 800; text-transform: uppercase;">ACCOUNTING &amp; CASHIER</div>
                    <div>Assessment &amp; Payment Cleared</div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar (Hidden on Print) -->
        <div class="no-print" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 24px; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeReceiptModal()" class="btn" style="padding: 8px 18px;">Close</button>
            <button type="button" onclick="window.printReceiptModal()" class="btn" style="background: #0b3d2e; color: #ffffff; border: 1px solid #0b3d2e; padding: 8px 22px; font-weight: 700; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Print Official Receipt</span>
            </button>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- MODAL: REQUEST REFUND MODAL                -->
<!-- ========================================== -->
<div class="modal-backdrop" id="requestRefundModal" style="display: none;">
    <div class="modal-window" style="max-width: 500px;">
        <button type="button" class="modal-close-x" id="btnCloseRequestRefundModal" onclick="closeRequestRefundModal()">&times;</button>
        <h2 class="modal-header-title">File Tuition Refund Request</h2>
        <p class="modal-header-sub" style="font-size: 13px; color: #64748b; margin-top: 2px; margin-bottom: 18px;">
            Submit your request with a valid justification reason for Accounting Office review.
        </p>

        <form method="POST" action="<?= APP_URL ?>/public/student/?view=refunds" id="refundRequestForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_refund">

            <!-- Select Assessment -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 13px; font-weight: 700; color: #0f172a;">Tuition Assessment to Refund *</label>
                <select class="form-control" name="fee_id" id="refundFeeSelect" onchange="onRefundFeeChanged()" required style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #0b3d2e; font-size: 13px;">
                    <option value="">-- Choose Tuition Assessment to Refund * --</option>
                    <?php if (empty($fees)): ?>
                        <option value="" disabled>No tuition assessments available</option>
                    <?php else: ?>
                        <?php foreach ($fees as $feeOpt): ?>
                            <?php 
                                $pPending = Refund::getPendingAmountForFee((int)$feeOpt['id']);
                                $maxRef = max(0.0, (float)$feeOpt['amount_paid'] - $pPending);
                            ?>
                            <option value="<?= (int)$feeOpt['id'] ?>" data-paid="<?= (float)$feeOpt['amount_paid'] ?>" data-max="<?= $maxRef ?>">
                                <?= e($feeOpt['description']) ?> (Paid: <?= peso((float)$feeOpt['amount_paid']) ?><?= $maxRef > 0 ? ' &bull; Max Refundable: ' . peso($maxRef) : ' &bull; No refundable funds' ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Select Optional Specific Payment OR -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 13px; font-weight: 700; color: #0f172a;">Related Payment Receipt (Optional)</label>
                <select class="form-control" name="payment_id" id="refundPaymentSelect" onchange="onRefundPaymentChanged()" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;">
                    <option value="" data-max="">-- Apply to Total Assessment Paid Balance --</option>
                    <?php foreach ($payments as $payOpt): ?>
                        <?php
                            $rAppr = Refund::getRefundedAmountForPayment((int)$payOpt['id']);
                            $rPend = Refund::getPendingAmountForPayment((int)$payOpt['id']);
                            $payMax = max(0.0, (float)$payOpt['amount'] - ($rAppr + $rPend));
                        ?>
                        <option value="<?= (int)$payOpt['id'] ?>" data-fee-id="<?= (int)$payOpt['tuition_fee_id'] ?>" data-max="<?= $payMax ?>">
                            OR# <?= e($payOpt['or_number']) ?> — <?= peso((float)$payOpt['amount']) ?> (<?= date('M d, Y', strtotime($payOpt['paid_at'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Select a specific OR receipt or apply refund directly to assessment credit.</div>
            </div>

            <!-- Available Max Refundable Box -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 12px; font-weight: 700; color: #166534;">Maximum Refundable Amount:</span>
                <span id="refundMaxDisplay" style="font-size: 15px; font-weight: 800; color: #15803d; font-family: monospace;">₱0.00 — Please select an assessment</span>
            </div>

            <!-- Refund Amount Input -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="refundAmountInput" style="font-size: 13px; font-weight: 700; color: #0f172a;">Refund Amount (₱) *</label>
                <input type="number" step="0.01" min="1" class="form-control" name="amount" id="refundAmountInput" required disabled placeholder="Select an assessment first..." style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px;">
            </div>

            <!-- Valid Reason for Refund (Mandatory Requirement) -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="refundReasonInput" style="font-size: 13px; font-weight: 700; color: #0f172a;">Valid Reason for Refund *</label>
                <textarea class="form-control" name="reason" id="refundReasonInput" rows="3" required minlength="10" placeholder="State your valid reason (e.g., dropped laboratory subject, course change, overpayment error, double deposit)..." style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; line-height: 1.45;"></textarea>
                <div style="font-size: 11.5px; color: #475569; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                    <span style="color: #b45309;">⚠️</span> Accounting requires a detailed, valid reason (at least 10 characters) before assessing this request.
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; margin-bottom: 20px; font-size: 11.5px; color: #64748b; line-height: 1.45;">
                Upon approval by Accounting, the refund is reflected on your student tuition balance and confirmation emails are dispatched to both student and parent email accounts.
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeRequestRefundModal()" class="btn" style="flex: 1; justify-content: center; padding: 11px; font-weight: 600;">Cancel</button>
                <button type="submit" class="btn" id="btnSubmitRefund" style="flex: 2; justify-content: center; padding: 11px; background: #0b3d2e; color: #ffffff; font-weight: 700; border: none; border-radius: 8px;">
                    Submit Refund Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ── SweetAlert Confirmations ──────────────────────────────
    const LOGOUT_URL = '<?= APP_URL ?>/public/?action=logout';

    document.getElementById('btnLogout').addEventListener('click', function () {
        Swal.fire({
            title: 'Log out?',
            text: 'You will be returned to the login page.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, log out',
            cancelButtonText: 'Stay',
            confirmButtonColor: '#18181b',
            cancelButtonColor: '#e4e4e7',
            customClass: { cancelButton: 'swal-cancel-dark' }
        }).then(result => {
            if (result.isConfirmed) window.location.href = LOGOUT_URL;
        });
    });

    // ── Drawer Navigation Elements ────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const btnOpenSidebar = document.getElementById('btnOpenSidebar');
    const btnCloseSidebar = document.getElementById('btnCloseSidebar');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (btnOpenSidebar) btnOpenSidebar.addEventListener('click', openSidebar);
    if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    if (sidebar) {
        sidebar.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }

    // ── Payment Modal Logic ───────────────────────────────────
    const paymentModal = document.getElementById('paymentInputModal');
    const btnClosePaymentModal = document.getElementById('btnClosePaymentInputModal');
    const modalFeeId = document.getElementById('modalFeeId');
    const modalFeeDesc = document.getElementById('modalFeeDesc');
    const modalFeeBalance = document.getElementById('modalFeeBalance');
    const payAmountInput = document.getElementById('payAmount');

    let currentRemaining = 0;

    function openPaymentModal(feeId, feeDesc, remaining) {
        modalFeeId.value = feeId;
        modalFeeDesc.textContent = feeDesc;
        modalFeeBalance.textContent = '₱' + parseFloat(remaining).toLocaleString('en-PH', {minimumFractionDigits: 2});
        payAmountInput.value = remaining > 0 ? remaining.toFixed(2) : '';
        payAmountInput.max = remaining;
        currentRemaining = remaining;
        paymentModal.classList.add('active');
    }

    if (btnClosePaymentModal) {
        btnClosePaymentModal.addEventListener('click', () => paymentModal.classList.remove('active'));
    }

    // Quick Select Handlers
    document.getElementById('pillFullPay')?.addEventListener('click', () => {
        payAmountInput.value = currentRemaining.toFixed(2);
    });
    document.getElementById('pillHalfPay')?.addEventListener('click', () => {
        payAmountInput.value = (currentRemaining / 2).toFixed(2);
    });
    document.getElementById('pill1k')?.addEventListener('click', () => {
        payAmountInput.value = Math.min(1000, currentRemaining).toFixed(2);
    });
    document.getElementById('pill5k')?.addEventListener('click', () => {
        payAmountInput.value = Math.min(5000, currentRemaining).toFixed(2);
    });

    // ── Request Refund Modal Logic ─────────────────────────────
    const refundModal = document.getElementById('requestRefundModal');
    const refundFeeSelect = document.getElementById('refundFeeSelect');
    const refundPaymentSelect = document.getElementById('refundPaymentSelect');
    const refundMaxDisplay = document.getElementById('refundMaxDisplay');
    const refundAmountInput = document.getElementById('refundAmountInput');
    const refundReasonInput = document.getElementById('refundReasonInput');
    const refundRequestForm = document.getElementById('refundRequestForm');

    let currentRefundMax = 0;

    function updateRefundLimit() {
        let maxVal = 0;
        const selectedFee = refundFeeSelect?.value;
        if (!selectedFee) {
            currentRefundMax = 0;
            if (refundMaxDisplay) {
                refundMaxDisplay.textContent = '₱0.00 — Please choose an assessment';
            }
            if (refundAmountInput) {
                refundAmountInput.disabled = true;
                refundAmountInput.value = '';
                refundAmountInput.placeholder = 'Select an assessment first...';
            }
            return;
        }

        const selectedPayOpt = refundPaymentSelect?.selectedOptions[0];
        const payMax = selectedPayOpt ? parseFloat(selectedPayOpt.getAttribute('data-max') || 0) : 0;

        if (refundPaymentSelect && refundPaymentSelect.value && payMax > 0) {
            maxVal = payMax;
        } else if (refundFeeSelect && refundFeeSelect.selectedOptions[0]) {
            const feeMax = parseFloat(refundFeeSelect.selectedOptions[0].getAttribute('data-max') || 0);
            maxVal = feeMax;
        }

        currentRefundMax = maxVal;
        if (refundAmountInput) {
            if (maxVal > 0) {
                refundAmountInput.disabled = false;
                refundAmountInput.max = maxVal;
                refundAmountInput.placeholder = 'Enter amount to refund (max ₱' + maxVal.toLocaleString('en-PH', {minimumFractionDigits: 2}) + ')';
                if (!refundAmountInput.value || parseFloat(refundAmountInput.value) > maxVal) {
                    refundAmountInput.value = maxVal.toFixed(2);
                }
            } else {
                refundAmountInput.disabled = true;
                refundAmountInput.value = '';
                refundAmountInput.placeholder = 'No refundable funds for this assessment';
            }
        }
        if (refundMaxDisplay) {
            if (maxVal > 0) {
                refundMaxDisplay.textContent = '₱' + maxVal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                refundMaxDisplay.textContent = '₱0.00 (No refundable balance)';
            }
        }
    }

    window.onRefundFeeChanged = function() {
        updateRefundLimit();
    };

    window.onRefundPaymentChanged = function() {
        const selectedPayOpt = refundPaymentSelect?.selectedOptions[0];
        if (selectedPayOpt && selectedPayOpt.getAttribute('data-fee-id')) {
            const feeId = selectedPayOpt.getAttribute('data-fee-id');
            if (refundFeeSelect) {
                refundFeeSelect.value = feeId;
            }
        }
        updateRefundLimit();
    };

    window.openRequestRefundModal = function() {
        if (!refundModal) return;
        updateRefundLimit();
        refundModal.style.display = 'flex';
        refundModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeRequestRefundModal = function() {
        if (!refundModal) return;
        refundModal.style.display = 'none';
        refundModal.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (refundRequestForm) {
        refundRequestForm.addEventListener('submit', function (e) {
            if (!refundFeeSelect || !refundFeeSelect.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Assessment Required',
                    text: 'Please select which tuition assessment you are requesting a refund for.'
                });
                return;
            }

            const reason = (refundReasonInput?.value || '').trim();
            const amount = parseFloat(refundAmountInput?.value || 0);

            if (currentRefundMax <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No Refundable Funds',
                    text: 'There are no eligible paid funds available to refund for this assessment.'
                });
                return;
            }

            if (amount <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Amount',
                    text: 'Please enter a positive refund amount.'
                });
                return;
            }

            if (amount > currentRefundMax) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Amount Exceeds Maximum',
                    text: 'The requested refund amount cannot exceed ₱' + currentRefundMax.toLocaleString('en-PH', {minimumFractionDigits: 2})
                });
                return;
            }

            if (reason.length < 10) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Valid Reason Required',
                    text: 'Accounting requires a detailed justification reason of at least 10 characters.'
                });
                return;
            }

            // Prompt user confirmation before submitting
            e.preventDefault();
            Swal.fire({
                title: 'Submit Refund Request?',
                html: `Are you sure you want to request a refund of <strong>₱${amount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong>?<br><br><small style="color:#64748b;">The Accounting Office will evaluate your stated reason. Once approved, the amount will be adjusted on your tuition ledger and confirmation emails will be sent.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit Request',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0b3d2e',
                cancelButtonColor: '#94a3b8'
            }).then(result => {
                if (result.isConfirmed) {
                    closeRequestRefundModal();
                    const overlay = document.getElementById('refundLoadingOverlay');
                    if (overlay) {
                        overlay.classList.add('active');
                    }
                    refundRequestForm.submit();
                }
            });
        });
    }

    // ── Student Notifications Dropdown Interaction ───────────
    const btnStudentNotif = document.getElementById('btnStudentNotif');
    const notifDropdownMenu = document.getElementById('notifDropdownMenu');
    const notifDotBadge = document.getElementById('notifDotBadge');
    const btnMarkAllRead = document.getElementById('btnMarkAllRead');

    if (btnStudentNotif && notifDropdownMenu) {
        btnStudentNotif.addEventListener('click', function(e) {
            e.stopPropagation();
            const isShown = notifDropdownMenu.style.display === 'block';
            if (isShown) {
                notifDropdownMenu.style.display = 'none';
                btnStudentNotif.classList.remove('active');
            } else {
                notifDropdownMenu.style.display = 'block';
                btnStudentNotif.classList.add('active');
                // Diminish / hide red unread dot badge upon checking notifications
                if (notifDotBadge) {
                    notifDotBadge.style.opacity = '0.3';
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notifDropdownMenu.contains(e.target) && !btnStudentNotif.contains(e.target)) {
                notifDropdownMenu.style.display = 'none';
                btnStudentNotif.classList.remove('active');
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && notifDropdownMenu.style.display === 'block') {
                notifDropdownMenu.style.display = 'none';
                btnStudentNotif.classList.remove('active');
            }
        });
    }

    if (btnMarkAllRead) {
        btnMarkAllRead.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.notif-row.unread').forEach(function(row) {
                row.classList.remove('unread');
            });
            document.querySelectorAll('.notif-item-pill').forEach(function(pill) {
                pill.remove();
            });
            const badgeCount = document.getElementById('notifBadgeCount');
            if (badgeCount) {
                badgeCount.textContent = '0 new';
                badgeCount.style.background = '#94a3b8';
            }
            if (notifDotBadge) {
                notifDotBadge.remove();
            }
            btnMarkAllRead.style.display = 'none';
        });
    }

    // ── Preloaded NCST Master Curriculum & Fee Schedule Engine ──
    window.NCST_CURRICULUM = <?= json_encode(Curriculum::getAllCatalogs()) ?>;

    function getCurriculumSchedule(courseSec, courseOverride) {
        const raw = String(courseSec || '41A2').trim();
        let year = 4, sem = 1, secSuffix = 'A2', course = 'BSIT';

        if (courseOverride && window.NCST_CURRICULUM.courses[courseOverride.toUpperCase()]) {
            course = courseOverride.toUpperCase();
        } else {
            const cMatch = raw.match(/\b(BSIT|BSCS|BSA|BSCRIM|BSCR|BSHM|BSIE|BSEE|ACT)\b/i);
            if (cMatch) {
                let found = cMatch[1].toUpperCase();
                if (found === 'BSCR') found = 'BSCRIM';
                if (found === 'ACT')  found = 'BSA';
                if (window.NCST_CURRICULUM.courses[found]) course = found;
            }
        }

        const m = raw.match(/([1-4])([1-2])([A-Z0-9]+)/i);
        if (m) {
            year = parseInt(m[1]);
            sem = parseInt(m[2]);
            secSuffix = m[3].toUpperCase();
        } else {
            const m2 = raw.match(/([1-4])([1-2])/i);
            if (m2) {
                year = parseInt(m2[1]);
                sem = parseInt(m2[2]);
                secSuffix = 'A1';
            }
        }

        const secCode = `${year}${sem}${secSuffix}`;
        const yearTexts = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };
        const semTexts = { 1: '1st Semester', 2: '2nd Semester' };

        const courseCatalog = (window.NCST_CURRICULUM.curricula && window.NCST_CURRICULUM.curricula[course]) 
            ? window.NCST_CURRICULUM.curricula[course] 
            : window.NCST_CURRICULUM.curricula['BSIT'];

        const key = `${year}${sem}`;
        const subs = courseCatalog[key] || courseCatalog['41'] || window.NCST_CURRICULUM.curricula['BSIT']['41'];
        let totUnits = 0;
        let labCount = 0;
        subs.forEach(s => {
            totUnits += (s.units || 3);
            if (String(s.type || '').includes('LAB')) labCount++;
        });

        const rates = window.NCST_CURRICULUM.rates || {
            unit: 645.95, lab: 1026.00, misc: 4743.49, lms: 2053.20, omr: 139.20, nstp: 1500.00
        };

        let tuitionTotal = 0, labTotal = 0;
        let miscBase = rates.misc, lmsFee = rates.lms, omrFee = rates.omr;
        let nstpFee = (year === 1) ? rates.nstp : 0.00;

        if (course === 'BSIT' && key === '41') {
            tuitionTotal = 8397.35;
            labTotal = 3078.00;
        } else {
            tuitionTotal = Math.round(totUnits * rates.unit * 100) / 100;
            labTotal = Math.round(labCount * rates.lab * 100) / 100;
        }

        let cashTotal = Math.round((tuitionTotal + labTotal + miscBase + lmsFee + omrFee + nstpFee) * 100) / 100;
        if (cashTotal > 29800) {
            tuitionTotal = Math.round((tuitionTotal - (cashTotal - 28950)) * 100) / 100;
            cashTotal = Math.round((tuitionTotal + labTotal + miscBase + lmsFee + omrFee + nstpFee) * 100) / 100;
        }

        const instCharge = Math.round((cashTotal * 0.099175) * 100) / 100;
        const instTotal = Math.round((cashTotal + instCharge) * 100) / 100;

        const breakdown = [
            { name: `Tuition Fee (${totUnits} Units @ ${rates.unit.toFixed(2)})`, amount: tuitionTotal },
            { name: `Laboratory Fee (${labCount} Science/Tech Lab Subjects)`, amount: labTotal },
            { name: 'Institutional Misc Fee (Reg, Library, Med/Dental, Energy)', amount: miscBase },
            { name: 'Learning Management System (LMS) & Campus Cloud', amount: lmsFee }
        ];
        if (nstpFee > 0) {
            breakdown.push({ name: 'NSTP / ROTC Component Fee (DND / CHED)', amount: nstpFee });
        }
        breakdown.push({ name: 'OMR / Exam Scoring & Educational Research', amount: omrFee });

        return {
            year, sem,
            yearText: yearTexts[year] || `${year}th Year`,
            semText: semTexts[sem] || `${sem}st Semester`,
            secCode, course,
            courseName: window.NCST_CURRICULUM.courses[course] || course,
            fullSection: `${course} - ${secCode}`,
            subjects: subs.map(s => ({
                ...s,
                section: secCode,
                start: s.start_time || s.start || '8:00 AM',
                end: s.end_time || s.end || '11:00 AM'
            })),
            totalUnits: totUnits,
            labCount,
            cashTotal, instCharge, instTotal,
            breakdown
        };
    }

    // ── Official Receipt Modal & Printable Logic ──────────────
    window.openOfficialReceiptModal = function(orNum, amount, date, method, feeDesc, studentName, studentNum, course) {
        const rcptOr = document.getElementById('rcptOrNumber');
        const rcptOrSide = document.getElementById('rcptOrNumberSide');
        const rcptD = document.getElementById('rcptDate');
        const rcptName = document.getElementById('rcptStudentName');
        const rcptId = document.getElementById('rcptStudentId');
        const rcptCourse = document.getElementById('rcptCourse');
        const rcptYearLevel = document.getElementById('rcptYearLevel');
        const rcptSec = document.getElementById('rcptCourseSection');
        const rcptSem = document.getElementById('rcptSchoolYearSem');
        const rcptMeth = document.getElementById('rcptPaymentMethod');
        const rcptDesc = document.getElementById('rcptFeeDesc');
        const rcptAmt = document.getElementById('rcptAmount');
        const rcptSig = document.getElementById('rcptSigStudent');

        const activeSec = course || '<?= e($student['grade_level'] ?? '41A2') ?>';
        const sched = getCurriculumSchedule(activeSec);

        if (rcptOr) rcptOr.textContent = orNum || 'OR-2024-00001';
        if (rcptOrSide) rcptOrSide.textContent = orNum || 'OR-2024-00001';
        if (rcptD) rcptD.textContent = date || '<?= date("m/d/Y h:i A") ?>';
        if (rcptName) rcptName.textContent = studentName || '<?= e($studentName) ?>';
        if (rcptSig) rcptSig.textContent = studentName || '<?= e($studentName) ?>';
        if (rcptId) rcptId.textContent = studentNum || '<?= e($studentNum) ?>';
        if (rcptCourse) rcptCourse.textContent = sched.course + ' - ' + sched.courseName;
        if (rcptYearLevel) rcptYearLevel.textContent = sched.yearText;
        if (rcptSec) rcptSec.textContent = sched.secCode + ' (' + sched.fullSection + ')';
        if (rcptSem) rcptSem.textContent = 'S.Y. 2026-2027 • ' + sched.semText;
        if (rcptMeth) rcptMeth.textContent = (method || 'ONLINE').toUpperCase();
        if (rcptDesc) rcptDesc.textContent = feeDesc || 'Tuition Fee Assessment Installment';
        if (rcptAmt) rcptAmt.textContent = amount || '₱0.00';

        // Render Specified Subjects Table
        const subTbody = document.getElementById('rcptSubjectsTbody');
        if (subTbody) {
            subTbody.innerHTML = sched.subjects.map(s => `
                <tr>
                    <td style="text-align: center; font-weight: 800; font-family: monospace; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.code)}</td>
                    <td style="border: 1px solid #000000; padding: 4px 6px;"><strong>${escapeHtml(s.description)}</strong></td>
                    <td style="text-align: center; font-weight: 800; border: 1px solid #000000; padding: 4px 6px;">${s.units}</td>
                    <td style="text-align: center; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.type)}</td>
                    <td style="text-align: center; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.days)}</td>
                    <td style="text-align: center; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.start)}</td>
                    <td style="text-align: center; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.end)}</td>
                    <td style="text-align: center; font-weight: 700; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.section)}</td>
                    <td style="text-align: center; border: 1px solid #000000; padding: 4px 6px;">${escapeHtml(s.room)}</td>
                </tr>
            `).join('');
        }
        const totUnitsEl = document.getElementById('rcptTotalUnits');
        if (totUnitsEl) totUnitsEl.textContent = sched.totalUnits;

        // Render Fee Assessment Table
        const feesTbody = document.getElementById('rcptFeesTbody');
        if (feesTbody) {
            feesTbody.innerHTML = sched.breakdown.map(b => `
                <tr style="border-bottom: 1px dashed #cbd5e1;">
                    <td style="padding: 3px 8px;">${escapeHtml(b.name)}</td>
                    <td style="padding: 3px 8px; text-align: right; font-weight: 700;">₱${b.amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        }

        const cashEl = document.getElementById('rcptCashTotal');
        if (cashEl) cashEl.textContent = '₱' + sched.cashTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const instChgEl = document.getElementById('rcptInstallmentCharge');
        if (instChgEl) instChgEl.textContent = '₱' + sched.instCharge.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const instTotEl = document.getElementById('rcptInstallmentTotal');
        if (instTotEl) instTotEl.textContent = '₱' + sched.instTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Calculate remaining balance
        const numAmt = parseFloat(String(amount || '0').replace(/[^0-9.]/g, '')) || 0;
        const rem = Math.max(0, sched.cashTotal - numAmt);
        const remEl = document.getElementById('rcptRemainingBalance');
        if (remEl) {
            remEl.textContent = '₱' + rem.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            remEl.style.color = rem > 0 ? '#b91c1c' : '#047857';
        }

        const modal = document.getElementById('officialReceiptModal');
        if (modal) {
            modal.classList.add('active');
        }
    };


    window.closeReceiptModal = function() {
        const modal = document.getElementById('officialReceiptModal');
        if (modal) {
            modal.classList.remove('active');
        }
    };

    window.printReceiptModal = function() {
        window.print();
    };

    window.printOfficialReceiptVoucher = function(orNum, amount, date, method, feeDesc, studentName, studentNum, course) {
        openOfficialReceiptModal(orNum, amount, date, method, feeDesc, studentName, studentNum, course);
        setTimeout(() => {
            window.print();
        }, 300);
    };

    window.viewReceiptSummary = function(orNum, amount, date, method, feeDesc) {
        openOfficialReceiptModal(orNum, amount, date, method, feeDesc, '<?= e($studentName) ?>', '<?= e($studentNum) ?>', '<?= e($student['grade_level'] ?? 'BSCS 11A1') ?>');
    };

    const officialReceiptModalEl = document.getElementById('officialReceiptModal');
    if (officialReceiptModalEl) {
        officialReceiptModalEl.addEventListener('click', function(e) {
            if (e.target === officialReceiptModalEl) {
                closeReceiptModal();
            }
        });
    }


    // ── Reusable red-field validation (replaces browser tooltip) ──────────────
    function applyRedFieldValidation(form) {
        if (!form) return;
        form.setAttribute('novalidate', '');
        form.addEventListener('submit', function (e) {
            let firstInvalid = null;
            form.querySelectorAll('[required]').forEach(function (field) {
                field.style.borderColor = '';
                field.style.background  = '';
                const existingMsg = field.parentElement.querySelector('.inline-field-error');
                if (existingMsg) existingMsg.remove();

                const isEmpty = field.tagName === 'SELECT'
                    ? field.value === '' || field.value === null
                    : !field.value.trim();

                if (isEmpty) {
                    e.preventDefault();
                    field.style.borderColor = '#ef4444';
                    field.style.background  = '#fef2f2';

                    const msg = document.createElement('span');
                    msg.className  = 'inline-field-error';
                    msg.textContent = 'This field is required.';
                    msg.style.cssText = 'color:#ef4444;font-size:11px;display:block;margin-top:4px;font-weight:600;';
                    field.parentElement.appendChild(msg);

                    if (!firstInvalid) firstInvalid = field;

                    field.addEventListener('input', function clear() {
                        field.style.borderColor = '';
                        field.style.background  = '';
                        const m = field.parentElement.querySelector('.inline-field-error');
                        if (m) m.remove();
                        field.removeEventListener('input', clear);
                    });
                    field.addEventListener('change', function clear() {
                        field.style.borderColor = '';
                        field.style.background  = '';
                        const m = field.parentElement.querySelector('.inline-field-error');
                        if (m) m.remove();
                        field.removeEventListener('change', clear);
                    });
                }
            });
            if (firstInvalid) firstInvalid.focus();
        });
    }

    applyRedFieldValidation(document.getElementById('paymentForm'));

    // Form Validation (Prevent Overpayment, Negative Numbers) & Trigger Loading Animation
    const paymentForm = document.getElementById('paymentForm');
    const btnSubmitPayment = document.getElementById('btnSubmitPayment');
    const paymentLoadingOverlay = document.getElementById('paymentLoadingOverlay');

    if (payAmountInput) {
        payAmountInput.addEventListener('keydown', function(e) {
            if (['-', '+', 'e', 'E'].includes(e.key)) {
                e.preventDefault();
            }
        });
        payAmountInput.addEventListener('input', function() {
            if (parseFloat(this.value) < 0) this.value = '';
        });
    }

    if (paymentForm) {
        paymentForm.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            const enteredAmount = parseFloat(payAmountInput.value) || 0;
            if (enteredAmount <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Please enter a valid payment amount greater than ₱0.',
                    confirmButtonColor: '#1e3a8a'
                });
                return false;
            }
            if (enteredAmount > currentRemaining) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Overpayment Not Allowed',
                    text: 'Payment amount (₱' + enteredAmount.toLocaleString('en-PH', {minimumFractionDigits: 2}) + ') cannot exceed your remaining balance of ₱' + currentRemaining.toLocaleString('en-PH', {minimumFractionDigits: 2}) + '.',
                    confirmButtonColor: '#e11d48'
                });
                return false;
            }

            // Validation passed: Show loading animation and lock button against double-click
            if (btnSubmitPayment) {
                btnSubmitPayment.disabled = true;
                btnSubmitPayment.innerHTML = '<span class="paytrack-inline-spinner"></span> Processing...';
            }
            if (paymentLoadingOverlay) {
                paymentLoadingOverlay.classList.add('active');
            }
        });
    }

    // Trigger Success Popup on Payment Flash
    <?php if (!empty($paymentSuccess)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Payment Successful!',
            html: `
                <div style="text-align: left; font-size: 13px; line-height: 1.8; color: #3f3f46;">
                    <p style="margin-bottom: 8px;">Your payment has been successfully recorded and an official receipt has been sent to your email.</p>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin: 10px 0;">
                        <p style="margin: 3px 0;"><strong>Receipt (OR#):</strong> <?= e($paymentSuccess['or_number']) ?></p>
                        <p style="margin: 3px 0;"><strong>Amount Paid:</strong> <?= peso((float)$paymentSuccess['amount']) ?></p>
                        <p style="margin: 3px 0;"><strong>Remaining Balance:</strong> <?= peso((float)$paymentSuccess['remaining']) ?></p>
                        <p style="margin: 3px 0;"><strong>Payment Method:</strong> <?= e($paymentSuccess['method'] ?? 'ONLINE') ?></p>
                        <p style="margin: 3px 0;"><strong>Date:</strong> <?= e($paymentSuccess['date']) ?></p>
                    </div>
                    <p style="font-size: 12px; color: #059669; margin-top: 6px;">Your tuition balance has been updated immediately.</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'View in Payment History &rarr;',
            cancelButtonText: 'Stay on This Page',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = '<?= APP_URL ?>/public/student/?view=history';
            }
        });
    <?php endif; ?>

    // Trigger Success Popup on Refund Flash
    <?php if (!empty($refundSuccess)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Refund Request Submitted!',
            html: `
                <div style="text-align: left; font-size: 13px; line-height: 1.8; color: #3f3f46;">
                    <p style="margin-bottom: 8px;">Your refund request has been successfully filed and sent to the Accounting Office for assessment.</p>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin: 10px 0;">
                        <p style="margin: 3px 0;"><strong>Reference No:</strong> <code style="font-weight: 700; color: #0b3d2e;"><?= e($refundSuccess['refund_number']) ?></code></p>
                        <p style="margin: 3px 0;"><strong>Assessment:</strong> <?= e($refundSuccess['fee_desc']) ?></p>
                        <p style="margin: 3px 0;"><strong>Refund Amount:</strong> <span style="font-weight: 700; color: #059669;"><?= peso((float)$refundSuccess['amount']) ?></span></p>
                        <p style="margin: 3px 0;"><strong>Status:</strong> <span style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">Pending Accounting Review</span></p>
                        <p style="margin: 3px 0;"><strong>Date:</strong> <?= e($refundSuccess['date']) ?></p>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin-top: 6px;">Once evaluated and approved, the amount will be adjusted on your tuition ledger, and an official confirmation email will be sent to both you and your parent.</p>
                </div>
            `,
            confirmButtonText: 'Understood',
            confirmButtonColor: '#0b3d2e'
        });
    <?php endif; ?>

    // ── Global Search & Live Autocomplete System (Student Portal) ──
    const STUDENT_PROFILE = {
        name: <?= json_encode($studentName) ?>,
        student_id: <?= json_encode($studentNum) ?>,
        section: <?= json_encode($student['grade_level'] ?? 'BSCS 11A1') ?>,
        balance: <?= json_encode(peso($totalRemaining)) ?>
    };

    const STUDENT_PAYMENTS = <?= json_encode(array_map(function($p) {
        return [
            'or_number' => $p['or_number'],
            'amount' => peso((float)$p['amount']),
            'raw_amount' => (float)$p['amount'],
            'fee_desc' => $p['fee_desc'],
            'method' => strtoupper($p['payment_method']),
            'date' => date('M d, Y h:i A', strtotime($p['paid_at']))
        ];
    }, $payments), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const STUDENT_BREAKDOWN = <?= json_encode(array_map(function($b) {
        return [
            'name' => $b['category_name'],
            'amount' => peso((float)$b['amount'])
        ];
    }, $breakdownItems ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const studentSearchInput = document.getElementById('studentSearchInput');
    const studentSearchDropdown = document.getElementById('studentSearchDropdown');

    function filterCurrentStudentView(query) {
        const q = (query || '').toLowerCase().trim();

        // 1. Filter Table Rows
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(r => {
            if (r.children.length === 1 && r.children[0].hasAttribute('colspan')) return;
            const text = r.textContent.toLowerCase();
            // Also match if user searched their own name or ID on home view
            const matchesStudent = STUDENT_PROFILE.name.toLowerCase().includes(q) || STUDENT_PROFILE.student_id.toLowerCase().includes(q);
            r.style.display = (text.includes(q) || (matchesStudent && q.length >= 3)) ? '' : 'none';
        });

        // 2. Filter Receipt Cards (on ?view=receipts)
        const cards = document.querySelectorAll('.receipt-card-item');
        cards.forEach(c => {
            const text = c.textContent.toLowerCase();
            c.style.display = text.includes(q) ? '' : 'none';
        });
    }

    function renderStudentSearchDropdown(query) {
        if (!studentSearchDropdown) return;
        if (!query || query.trim().length === 0) {
            studentSearchDropdown.style.display = 'none';
            studentSearchDropdown.innerHTML = '';
            return;
        }

        const q = query.trim().toLowerCase();
        let html = '';
        let matchCount = 0;

        // 1. Match Student Account Profile (e.g. "Dela Cruz", "2023", "BSCS")
        const nameMatches = STUDENT_PROFILE.name.toLowerCase().includes(q);
        const idMatches = STUDENT_PROFILE.student_id.toLowerCase().includes(q);
        const secMatches = STUDENT_PROFILE.section.toLowerCase().includes(q);

        if (nameMatches || idMatches || secMatches) {
            matchCount++;
            html += `<div class="search-dropdown-group-title">Student Profile</div>`;
            html += `
                <div class="search-dropdown-item" style="cursor: default; background: #f0fdf4;">
                    <div>
                        <strong style="color: #166534;">👤 ${escapeHtml(STUDENT_PROFILE.name)}</strong>
                        <span class="item-meta"> &bull; ID: <code>${escapeHtml(STUDENT_PROFILE.student_id)}</code> &bull; ${escapeHtml(STUDENT_PROFILE.section)}</span>
                    </div>
                    <div style="font-weight: 700; font-size: 11px; color: #166534;">
                        Remaining: ${escapeHtml(STUDENT_PROFILE.balance)}
                    </div>
                </div>
            `;
        }

        // 2. Match Official Receipts
        const matchedPayments = STUDENT_PAYMENTS.filter(p => {
            return p.or_number.toLowerCase().includes(q) ||
                   p.fee_desc.toLowerCase().includes(q) ||
                   p.method.toLowerCase().includes(q) ||
                   p.amount.toLowerCase().includes(q) ||
                   p.date.toLowerCase().includes(q);
        }).slice(0, 4);

        if (matchedPayments.length > 0) {
            matchCount += matchedPayments.length;
            html += `<div class="search-dropdown-group-title">Official Receipts (${matchedPayments.length})</div>`;
            matchedPayments.forEach(p => {
                html += `
                    <div class="search-dropdown-item" onclick="openOfficialReceiptModal('${escapeHtml(p.or_number)}', '${escapeHtml(p.amount)}', '${escapeHtml(p.date)}', '${escapeHtml(p.method)}', '${escapeHtml(p.fee_desc)}', '${escapeHtml(STUDENT_PROFILE.name)}', '${escapeHtml(STUDENT_PROFILE.student_id)}', '${escapeHtml(STUDENT_PROFILE.section)}')">
                        <div>
                            <strong style="font-family: monospace; color: #0b3d2e;">🧾 ${escapeHtml(p.or_number)}</strong>
                            <span class="item-meta"> &bull; ${escapeHtml(p.method)} &bull; ${escapeHtml(p.date)}</span>
                        </div>
                        <div style="font-weight: 800; font-size: 12px; color: #059669;">
                            ${escapeHtml(p.amount)}
                        </div>
                    </div>
                `;
            });
        }

        // 3. Match Fee Aspects Breakdown
        const matchedAspects = STUDENT_BREAKDOWN.filter(b => {
            return b.name.toLowerCase().includes(q) || b.amount.toLowerCase().includes(q);
        }).slice(0, 4);

        if (matchedAspects.length > 0) {
            matchCount += matchedAspects.length;
            html += `<div class="search-dropdown-group-title">Tuition Fee Aspects (${matchedAspects.length})</div>`;
            matchedAspects.forEach(b => {
                html += `
                    <a href="<?= APP_URL ?>/public/student/?view=fees" class="search-dropdown-item">
                        <div>
                            <strong style="color: #0f172a;">💳 ${escapeHtml(b.name)}</strong>
                        </div>
                        <div style="font-weight: 700; font-size: 12px; color: #2563eb;">
                            ${escapeHtml(b.amount)}
                        </div>
                    </a>
                `;
            });
        }

        if (matchCount === 0) {
            studentSearchDropdown.innerHTML = `
                <div class="search-dropdown-empty">
                    No matching records found for "<strong>${escapeHtml(query)}</strong>".<br>
                    <small style="color: #94a3b8; margin-top: 4px; display: inline-block;">Try searching for OR#, fee aspect, amount, or date</small>
                </div>
            `;
            studentSearchDropdown.style.display = 'block';
            return;
        }

        studentSearchDropdown.innerHTML = html;
        studentSearchDropdown.style.display = 'block';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
        });
    }

    if (studentSearchInput) {
        studentSearchInput.addEventListener('input', function() {
            const query = this.value;
            filterCurrentStudentView(query);
            renderStudentSearchDropdown(query);
        });

        studentSearchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                renderStudentSearchDropdown(this.value);
            }
        });

        studentSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (studentSearchDropdown) studentSearchDropdown.style.display = 'none';
            }
        });

        // Pre-fill search if ?q= is in URL
        const urlParams = new URLSearchParams(window.location.search);
        const initialQuery = urlParams.get('q');
        if (initialQuery && initialQuery.trim().length > 0) {
            studentSearchInput.value = initialQuery;
            filterCurrentStudentView(initialQuery);
        }
    }

    // Close search dropdown on click outside
    document.addEventListener('click', function(e) {
        if (studentSearchDropdown && !studentSearchDropdown.contains(e.target) && e.target !== studentSearchInput) {
            studentSearchDropdown.style.display = 'none';
        }
    });
</script>

<script>
    // ── Real-Time Tuition & Assessment Polling for Student ──
    let lastKnownTotal = '<?= peso($totalFees) ?>';
    let lastKnownRemaining = '<?= peso($totalRemaining) ?>';
    let lastKnownPaid = '<?= peso($totalPaid) ?>';

    async function pollStudentLiveStatus() {
        try {
            const resp = await fetch('<?= APP_URL ?>/public/student/?action=live_status', { cache: 'no-store' });
            if (!resp.ok) return;
            const data = await resp.json();
            if (!data.success) return;

            // If tuition total or remaining balance changed, update DOM
            if (data.formatted_remaining !== lastKnownRemaining || data.formatted_total !== lastKnownTotal || data.formatted_paid !== lastKnownPaid) {
                lastKnownTotal = data.formatted_total;
                lastKnownRemaining = data.formatted_remaining;
                lastKnownPaid = data.formatted_paid;

                // Update balance cards and labels
                document.querySelectorAll('.balance-huge-red').forEach(el => el.textContent = data.formatted_remaining);
                document.querySelectorAll('.metric-mini-val').forEach(el => {
                    if (el.style.color.includes('220') || el.style.color.includes('dc2626')) {
                        el.textContent = data.formatted_remaining;
                    }
                });

                // Update progress bar
                const progressFill = document.querySelector('.balance-progress-fill') || document.querySelector('.progress-bar');
                if (progressFill) {
                    progressFill.style.width = data.percentage + '%';
                }

                // Show discreet toast
                if (window.Swal) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'info',
                        title: 'Tuition assessment updated by Accounting Office.'
                    });
                }
            }
        } catch (e) {
            // Silently ignore network interruptions
        }
    }

    // Poll every 6 seconds and on window focus
    setInterval(pollStudentLiveStatus, 6000);
    window.addEventListener('focus', pollStudentLiveStatus);
</script>

<!-- Bootstrap 5.3 JS (CDN with Subresource Integrity & Local Fallback) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= APP_URL ?>/assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
