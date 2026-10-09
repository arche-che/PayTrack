<?php

/**
 * Sanitize output to prevent XSS
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL and exit
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Generate a unique Official Receipt number
 * Format: OR-YYYY-XXXXX  (e.g. OR-2024-00042)
 */
function generateORNumber(): string
{
    return 'OR-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Format a decimal as Philippine peso
 */
function peso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

/**
 * Generate the default password: literal LASTNAME of the student (UPPERCASED, stripped of special chars/spaces)
 */
function defaultPassword(string $studentId, string $lastName): string
{
    return preg_replace('/[^A-Za-z0-9]/', '', strtoupper($lastName));
}

/**
 * CSRF Protection
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        if (class_exists('Auth')) {
            Auth::start();
        } else {
            session_start();
        }
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (class_exists('Auth')) {
            Auth::start();
        } else {
            session_start();
        }
    }
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die("Security Validation Failed: Invalid CSRF Token. Please refresh the page and try again.");
    }
}

/**
 * Validate Student ID format (letters, digits, dashes, 3-20 chars)
 */
function isValidStudentId(string $id): bool
{
    return (bool) preg_match('/^[A-Za-z0-9\-]{3,20}$/', $id);
}

/**
 * Validate Email address format
 */
function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Validate an email address restricted to Google's gmail.com domain. */
function isValidGmail(string $email): bool
{
    return isValidEmail($email) && strtolower(substr(strrchr($email, '@') ?: '', 1)) === 'gmail.com';
}

/**
 * Validate Name (letters, spaces, hyphens, min 2 chars)
 */
function isValidName(string $name): bool
{
    return (bool) preg_match('/^[A-Za-z\s\.\-]{2,60}$/', $name);
}

/**
 * Generate a unique Refund Request reference number
 * Format: REF-YYYY-XXXXX (e.g. REF-2026-00042)
 */
function generateRefundNumber(): string
{
    return 'REF-' . date('Y') . '-' . str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

// NCST Specified Subjects & Curriculum Schedule Engine
require_once __DIR__ . '/Curriculum.php';

