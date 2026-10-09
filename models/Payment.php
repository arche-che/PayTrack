<?php
require_once __DIR__ . '/../config/database.php';

class Payment
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT p.*, s.first_name, s.last_name, s.student_id as student_num, s.grade_level, tf.description as fee_desc
                FROM payments p
                JOIN students s ON p.student_id = s.id
                JOIN tuition_fees tf ON p.tuition_fee_id = tf.id
                ORDER BY p.paid_at DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public static function getByStudentId(int $studentId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, tf.description as fee_desc
                              FROM payments p
                              JOIN tuition_fees tf ON p.tuition_fee_id = tf.id
                              WHERE p.student_id = ?
                              ORDER BY p.paid_at DESC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public static function getByFeeId(int $feeId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM payments WHERE tuition_fee_id = ? ORDER BY paid_at DESC, id DESC");
        $stmt->execute([$feeId]);
        return $stmt->fetchAll();
    }

    public static function create(int $feeId, int $studentId, string $orNumber, float $amount, string $method = 'cash', ?string $notes = null): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO payments (tuition_fee_id, student_id, or_number, amount, payment_method, notes, paid_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$feeId, $studentId, $orNumber, $amount, $method, $notes]);
        return (int) $db->lastInsertId();
    }
}
