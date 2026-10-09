<?php
require_once __DIR__ . '/../config/database.php';

class Payment
{
    private static bool $subjectSnapshotSchemaReady = false;

    /** Add the history column for databases that were created before subject snapshots were introduced. */
    private static function ensureSubjectSnapshotColumn(): void
    {
        if (self::$subjectSnapshotSchemaReady) {
            return;
        }

        $db = Database::getInstance();
        $check = $db->query("SHOW COLUMNS FROM `payments` LIKE 'enrolled_subjects_snapshot'");
        if (!$check->fetch()) {
            try {
                $db->exec("ALTER TABLE `payments` ADD COLUMN `enrolled_subjects_snapshot` LONGTEXT NULL AFTER `notes`");
            } catch (PDOException $e) {
                // Another request may have added it at the same time; only continue if it now exists.
                $verify = $db->query("SHOW COLUMNS FROM `payments` LIKE 'enrolled_subjects_snapshot'");
                if (!$verify->fetch()) {
                    throw $e;
                }
            }
        }

        self::$subjectSnapshotSchemaReady = true;
    }

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
        self::ensureSubjectSnapshotColumn();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT p.*, tf.description as fee_desc, tf.enrolled_subjects as current_enrolled_subjects
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
        self::ensureSubjectSnapshotColumn();
        $db = Database::getInstance();
        $feeStmt = $db->prepare("SELECT enrolled_subjects FROM tuition_fees WHERE id = ?");
        $feeStmt->execute([$feeId]);
        $subjects = $feeStmt->fetchColumn();
        $stmt = $db->prepare("INSERT INTO payments (tuition_fee_id, student_id, or_number, amount, payment_method, notes, enrolled_subjects_snapshot, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$feeId, $studentId, $orNumber, $amount, $method, $notes, $subjects ?: null]);
        return (int) $db->lastInsertId();
    }
}
