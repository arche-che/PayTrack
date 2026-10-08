<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/FeeCategory.php';

class TuitionFee
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT tf.*, s.first_name, s.last_name, s.student_id as student_num, s.grade_level
                FROM tuition_fees tf
                JOIN students s ON tf.student_id = s.id
                ORDER BY tf.id DESC";
        $stmt = $db->query($sql);
        $fees = $stmt->fetchAll();

        foreach ($fees as &$fee) {
            $fee['items'] = self::getItems($fee['id']);
        }
        return $fees;
    }

    public static function getByStudentId(int $studentId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tuition_fees WHERE student_id = ? ORDER BY id DESC");
        $stmt->execute([$studentId]);
        $fees = $stmt->fetchAll();

        foreach ($fees as &$fee) {
            $fee['items'] = self::getItems($fee['id']);
        }
        return $fees;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT tf.*, s.first_name, s.last_name, s.student_id as student_num, s.email, s.parent_email
                              FROM tuition_fees tf
                              JOIN students s ON tf.student_id = s.id
                              WHERE tf.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['items'] = self::getItems($row['id']);
        }
        return $row ?: null;
    }

    public static function getItems(int $tuitionFeeId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tuition_fee_items WHERE tuition_fee_id = ? ORDER BY id ASC");
        $stmt->execute([$tuitionFeeId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a tuition fee record and auto-split into fee aspects:
     * - Fixed categories (LMS, Misc, etc.) get their default amounts
     * - Variable category (Subject Fee) gets the remaining balance
     */
    public static function createWithBreakdown(int $studentId, float $totalAmount, string $schoolYear, string $semester, ?string $dueDate = null): int
    {
        $db = Database::getInstance();
        $desc = "S.Y. {$schoolYear} - {$semester} Tuition";

        $stmt = $db->prepare(
            "INSERT INTO tuition_fees (student_id, school_year, semester, description, total_amount, amount_paid, due_date, status)
             VALUES (?, ?, ?, ?, ?, 0.00, ?, 'unpaid')"
        );
        $stmt->execute([$studentId, $schoolYear, $semester, $desc, $totalAmount, $dueDate ?: null]);
        $feeId = (int) $db->lastInsertId();

        // Calculate and insert aspect items
        $categories = FeeCategory::allActive();
        $fixedSum = 0.0;
        $variableCat = null;

        foreach ($categories as $cat) {
            if (!empty($cat['is_variable'])) {
                $variableCat = $cat;
            } else {
                $fixedSum += (float) $cat['default_amount'];
            }
        }

        $itemStmt = $db->prepare(
            "INSERT INTO tuition_fee_items (tuition_fee_id, fee_category_id, category_name, amount) VALUES (?, ?, ?, ?)"
        );

        foreach ($categories as $cat) {
            if (!empty($cat['is_variable'])) {
                $amount = max(0.00, $totalAmount - $fixedSum);
            } else {
                $amount = (float) $cat['default_amount'];
            }

            $itemStmt->execute([
                $feeId,
                $cat['id'],
                $cat['name'],
                $amount
            ]);
        }

        return $feeId;
    }

    public static function findLatestByStudentId(int $studentId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tuition_fees WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['items'] = self::getItems($row['id']);
        }
        return $row ?: null;
    }

    public static function recordPayment(int $feeId, float $amount): void
    {
        $db = Database::getInstance();
        $fee = self::findById($feeId);
        if (!$fee) return;

        $newPaid = round((float) $fee['amount_paid'] + $amount, 2);
        $total = round((float) $fee['total_amount'], 2);

        $status = 'unpaid';
        if ($newPaid >= $total) {
            $status = 'paid';
            $newPaid = $total; // Cap paid amount at total to ensure clean 0 remaining balance
        } elseif ($newPaid > 0) {
            $status = 'partial';
        }

        $stmt = $db->prepare("UPDATE tuition_fees SET amount_paid = ?, status = ? WHERE id = ?");
        $stmt->execute([$newPaid, $status, $feeId]);
    }

    public static function processRefund(int $feeId, float $amount): void
    {
        $db = Database::getInstance();
        $fee = self::findById($feeId);
        if (!$fee) return;

        $currentPaid = (float) $fee['amount_paid'];
        $newPaid = round(max(0.0, $currentPaid - $amount), 2);
        $total = round((float) $fee['total_amount'], 2);

        $status = 'unpaid';
        if ($newPaid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($newPaid > 0) {
            $status = 'partial';
        }

        $stmt = $db->prepare("UPDATE tuition_fees SET amount_paid = ?, status = ? WHERE id = ?");
        $stmt->execute([$newPaid, $status, $feeId]);
    }

    public static function createCustomAssessment(
        int $studentId,
        string $schoolYear,
        string $semester,
        string $description,
        ?string $dueDate,
        array $items
    ): int {
        $db = Database::getInstance();

        $totalAmount = 0.0;
        foreach ($items as $it) {
            $totalAmount += max(0.0, (float)($it['amount'] ?? 0));
        }
        $totalAmount = round($totalAmount, 2);

        $stmt = $db->prepare(
            "INSERT INTO tuition_fees (student_id, school_year, semester, description, total_amount, amount_paid, due_date, status)
             VALUES (?, ?, ?, ?, ?, 0.00, ?, 'unpaid')"
        );
        $stmt->execute([$studentId, $schoolYear, $semester, $description, $totalAmount, $dueDate ?: null]);
        $feeId = (int) $db->lastInsertId();

        if (!empty($items)) {
            $itemStmt = $db->prepare(
                "INSERT INTO tuition_fee_items (tuition_fee_id, fee_category_id, category_name, amount) VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $it) {
                $catId = !empty($it['fee_category_id']) && (int)$it['fee_category_id'] > 0 ? (int)$it['fee_category_id'] : null;
                $catName = trim($it['category_name'] ?? 'Fee Item');
                $amt = round(max(0.0, (float)($it['amount'] ?? 0)), 2);
                $itemStmt->execute([$feeId, $catId, $catName, $amt]);
            }
        }

        return $feeId;
    }

    public static function updateCustomAssessment(
        int $feeId,
        int $studentId,
        string $schoolYear,
        string $semester,
        string $description,
        ?string $dueDate,
        array $items
    ): bool {
        $db = Database::getInstance();
        $existing = self::findById($feeId);
        if (!$existing) {
            return false;
        }

        $totalAmount = 0.0;
        foreach ($items as $it) {
            $totalAmount += max(0.0, (float)($it['amount'] ?? 0));
        }
        $totalAmount = round($totalAmount, 2);
        $paidAmount  = round((float)($existing['amount_paid'] ?? 0), 2);

        $status = 'unpaid';
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        $stmt = $db->prepare(
            "UPDATE tuition_fees 
             SET school_year = ?, semester = ?, description = ?, total_amount = ?, due_date = ?, status = ?
             WHERE id = ?"
        );
        $stmt->execute([$schoolYear, $semester, $description, $totalAmount, $dueDate ?: null, $status, $feeId]);

        // Delete old items and re-insert updated items
        $db->prepare("DELETE FROM tuition_fee_items WHERE tuition_fee_id = ?")->execute([$feeId]);

        if (!empty($items)) {
            $itemStmt = $db->prepare(
                "INSERT INTO tuition_fee_items (tuition_fee_id, fee_category_id, category_name, amount) VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $it) {
                $catId = !empty($it['fee_category_id']) && (int)$it['fee_category_id'] > 0 ? (int)$it['fee_category_id'] : null;
                $catName = trim($it['category_name'] ?? 'Fee Item');
                $amt = round(max(0.0, (float)($it['amount'] ?? 0)), 2);
                $itemStmt->execute([$feeId, $catId, $catName, $amt]);
            }
        }

        return true;
    }

    public static function delete(int $id): void
    {
        $db = Database::getInstance();

        // Must remove child rows first to satisfy FK constraints:
        // 1. refund_requests reference tuition_fees
        $db->prepare("DELETE FROM refund_requests WHERE tuition_fee_id = ?")->execute([$id]);
        // 2. payments reference tuition_fees (fk_payments_fee)
        $db->prepare("DELETE FROM payments WHERE tuition_fee_id = ?")->execute([$id]);
        // 3. tuition_fee_items reference tuition_fees
        $db->prepare("DELETE FROM tuition_fee_items WHERE tuition_fee_id = ?")->execute([$id]);
        // 4. Now safe to delete the parent
        $db->prepare("DELETE FROM tuition_fees WHERE id = ?")->execute([$id]);
    }
}
