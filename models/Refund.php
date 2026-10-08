<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TuitionFee.php';

class Refund
{
    /**
     * Retrieve all refund requests with joined student, fee, payment, and reviewer details
     */
    public static function all(?string $status = null): array
    {
        $db = Database::getInstance();
        $sql = "SELECT r.*, 
                       s.first_name, s.last_name, s.student_id as student_num, s.grade_level, s.email, s.parent_email, s.parent_name,
                       tf.description as fee_desc, tf.total_amount as fee_total, tf.amount_paid as fee_paid,
                       p.or_number, p.payment_method, p.amount as payment_amount, p.paid_at as payment_date,
                       u.name as reviewer_name, u.username as reviewer_username
                FROM refund_requests r
                JOIN students s ON r.student_id = s.id
                JOIN tuition_fees tf ON r.tuition_fee_id = tf.id
                LEFT JOIN payments p ON r.payment_id = p.id
                LEFT JOIN users u ON r.reviewed_by = u.id";

        if ($status !== null && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= " WHERE r.status = ?";
            $stmt = $db->prepare($sql . " ORDER BY r.requested_at DESC, r.id DESC");
            $stmt->execute([$status]);
        } else {
            $stmt = $db->query($sql . " ORDER BY r.requested_at DESC, r.id DESC");
        }

        return $stmt->fetchAll();
    }

    /**
     * Retrieve refund requests for a specific student
     */
    public static function getByStudentId(int $studentId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT r.*, 
                                     tf.description as fee_desc, tf.total_amount as fee_total, tf.amount_paid as fee_paid,
                                     p.or_number, p.payment_method, p.amount as payment_amount, p.paid_at as payment_date,
                                     u.name as reviewer_name
                              FROM refund_requests r
                              JOIN tuition_fees tf ON r.tuition_fee_id = tf.id
                              LEFT JOIN payments p ON r.payment_id = p.id
                              LEFT JOIN users u ON r.reviewed_by = u.id
                              WHERE r.student_id = ?
                              ORDER BY r.requested_at DESC, r.id DESC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Find single refund request by ID with joined details
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT r.*, 
                                     s.first_name, s.last_name, s.student_id as student_num, s.grade_level, s.email, s.parent_email, s.parent_name,
                                     tf.description as fee_desc, tf.total_amount as fee_total, tf.amount_paid as fee_paid,
                                     p.or_number, p.payment_method, p.amount as payment_amount, p.paid_at as payment_date,
                                     u.name as reviewer_name, u.username as reviewer_username
                              FROM refund_requests r
                              JOIN students s ON r.student_id = s.id
                              JOIN tuition_fees tf ON r.tuition_fee_id = tf.id
                              LEFT JOIN payments p ON r.payment_id = p.id
                              LEFT JOIN users u ON r.reviewed_by = u.id
                              WHERE r.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Submit a new student refund request
     */
    public static function create(int $studentId, int $tuitionFeeId, ?int $paymentId, float $amount, string $reason): int
    {
        $db = Database::getInstance();
        $refundNumber = generateRefundNumber();

        // Ensure unique refund number in rare case of collision
        $check = $db->prepare("SELECT id FROM refund_requests WHERE refund_number = ? LIMIT 1");
        $check->execute([$refundNumber]);
        if ($check->fetch()) {
            $refundNumber = 'REF-' . date('Y') . '-' . mt_rand(100000, 999999);
        }

        $stmt = $db->prepare("
            INSERT INTO refund_requests (refund_number, student_id, tuition_fee_id, payment_id, amount, reason, status, requested_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $refundNumber,
            $studentId,
            $tuitionFeeId,
            $paymentId > 0 ? $paymentId : null,
            round($amount, 2),
            trim($reason)
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Approve a pending refund request and deduct the refunded amount from tuition_fees.amount_paid
     */
    public static function approve(int $id, int $reviewerUserId, ?string $notes = null): bool
    {
        $db = Database::getInstance();
        $refund = self::findById($id);

        if (!$refund || $refund['status'] !== 'pending') {
            return false;
        }

        $feeId = (int) $refund['tuition_fee_id'];
        $amount = (float) $refund['amount'];

        $db->beginTransaction();
        try {
            // 1. Update refund request record to approved
            $stmt = $db->prepare("
                UPDATE refund_requests 
                SET status = 'approved',
                    reviewed_by = ?,
                    review_notes = ?,
                    reviewed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$reviewerUserId, trim((string)$notes), $id]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }

            // 2. Adjust tuition fee amount_paid (deduct refunded amount)
            TuitionFee::processRefund($feeId, $amount);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Refund approval error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject a pending refund request with explanation reason
     */
    public static function reject(int $id, int $reviewerUserId, string $rejectionReason): bool
    {
        $db = Database::getInstance();
        $refund = self::findById($id);

        if (!$refund || $refund['status'] !== 'pending') {
            return false;
        }

        $stmt = $db->prepare("
            UPDATE refund_requests 
            SET status = 'rejected',
                reviewed_by = ?,
                rejection_reason = ?,
                reviewed_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        return $stmt->execute([$reviewerUserId, trim($rejectionReason), $id]);
    }

    /**
     * Count pending refund requests for accounting review badges
     */
    public static function countPending(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM refund_requests WHERE status = 'pending'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count pending refund requests for a specific student
     */
    public static function countPendingByStudentId(int $studentId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM refund_requests WHERE student_id = ? AND status = 'pending'");
        $stmt->execute([$studentId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Total approved refunds across the institution
     */
    public static function getTotalApprovedAmount(): float
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE status = 'approved'");
        return (float) $stmt->fetchColumn();
    }

    /**
     * Total approved refunds for a single student
     */
    public static function getTotalApprovedAmountByStudentId(int $studentId): float
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE student_id = ? AND status = 'approved'");
        $stmt->execute([$studentId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Sum of currently pending refund requests for a specific tuition fee
     */
    public static function getPendingAmountForFee(int $feeId, ?int $excludeRefundId = null): float
    {
        $db = Database::getInstance();
        if ($excludeRefundId) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE tuition_fee_id = ? AND status = 'pending' AND id != ?");
            $stmt->execute([$feeId, $excludeRefundId]);
        } else {
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE tuition_fee_id = ? AND status = 'pending'");
            $stmt->execute([$feeId]);
        }
        return (float) $stmt->fetchColumn();
    }

    /**
     * Sum of approved refunds for a specific payment
     */
    public static function getRefundedAmountForPayment(int $paymentId): float
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE payment_id = ? AND status = 'approved'");
        $stmt->execute([$paymentId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Sum of pending refunds for a specific payment
     */
    public static function getPendingAmountForPayment(int $paymentId): float
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM refund_requests WHERE payment_id = ? AND status = 'pending'");
        $stmt->execute([$paymentId]);
        return (float) $stmt->fetchColumn();
    }
}
