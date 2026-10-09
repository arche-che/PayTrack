<?php
require_once __DIR__ . '/../config/database.php';

class FeeCategory
{
    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT fc.*, u.username as requester_username, u.name as requester_name 
            FROM fee_categories fc 
            LEFT JOIN users u ON fc.requested_by = u.id 
            ORDER BY fc.sort_order ASC, fc.id ASC
        ')->fetchAll();
    }

    public static function allActive(): array
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM fee_categories WHERE is_active = 1 AND approval_status = 'approved' ORDER BY sort_order ASC, id ASC")->fetchAll();
    }

    public static function getPendingApprovals(): array
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT fc.*, u.username as requester_username, u.name as requester_name, u.email as requester_email
            FROM fee_categories fc 
            LEFT JOIN users u ON fc.requested_by = u.id 
            WHERE fc.approval_status = 'pending' 
            ORDER BY fc.requested_at DESC, fc.id DESC
        ")->fetchAll();
    }

    public static function countPendingApprovals(): int
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT COUNT(*) AS cnt FROM fee_categories WHERE approval_status = 'pending'")->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT fc.*, u.username as requester_username, u.name as requester_name FROM fee_categories fc LEFT JOIN users u ON fc.requested_by = u.id WHERE fc.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM fee_categories WHERE code = ? LIMIT 1');
        $stmt->execute([strtolower(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByNameOrCode(string $term): ?array
    {
        $db = Database::getInstance();
        $clean = trim($term);
        $stmt = $db->prepare('SELECT * FROM fee_categories WHERE code = ? OR LOWER(name) LIKE ? LIMIT 1');
        $stmt->execute([strtolower($clean), '%' . strtolower($clean) . '%']);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getVariableCategory(): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM fee_categories WHERE is_variable = 1 AND is_active = 1 AND approval_status = 'approved' LIMIT 1");
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getFixedTotal(): float
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT COALESCE(SUM(default_amount), 0) AS total FROM fee_categories WHERE is_variable = 0 AND is_active = 1 AND approval_status = 'approved'")->fetch();
        return (float) ($row['total'] ?? 0);
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $isVar = !empty($data['is_variable']) ? 1 : 0;
        if ($isVar) {
            $db->query('UPDATE fee_categories SET is_variable = 0');
        }
        $stmt = $db->prepare(
            'INSERT INTO fee_categories (name, code, default_amount, is_variable, sort_order, is_active, approval_status, requested_by, requested_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['name'],
            $data['code'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $data['name'])),
            $isVar ? 0.00 : (float) ($data['default_amount'] ?? 0),
            $isVar,
            (int) ($data['sort_order'] ?? 99),
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
            $data['approval_status'] ?? 'approved',
            $data['requested_by'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Accounting staff submits a request for a new fee category that requires Admin Approval.
     */
    public static function requestCreate(array $data, int $requestedByUserId): int
    {
        $db = Database::getInstance();
        $isVar = !empty($data['is_variable']) ? 1 : 0;
        $code = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($data['name'])));
        if (empty($code)) $code = 'fee_' . time();
        
        // Ensure uniqueness of code if another category already has it
        $chk = $db->prepare("SELECT COUNT(*) as cnt FROM fee_categories WHERE code = ?");
        $chk->execute([$code]);
        if (($chk->fetch()['cnt'] ?? 0) > 0) {
            $code .= '_' . time();
        }

        $stmt = $db->prepare(
            "INSERT INTO fee_categories (name, code, default_amount, is_variable, sort_order, is_active, approval_status, requested_by, requested_at) 
             VALUES (?, ?, ?, ?, ?, 0, 'pending', ?, NOW())"
        );
        $stmt->execute([
            trim($data['name']),
            $code,
            $isVar ? 0.00 : (float) ($data['default_amount'] ?? 0),
            $isVar,
            (int) ($data['sort_order'] ?? 99),
            $requestedByUserId,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function approve(int $id, int $adminUserId): void
    {
        $db = Database::getInstance();
        $cat = self::findById($id);
        if (!$cat) return;

        if (!empty($cat['is_variable'])) {
            $db->prepare("UPDATE fee_categories SET is_variable = 0 WHERE id != ?")->execute([$id]);
        }

        $stmt = $db->prepare("UPDATE fee_categories SET approval_status = 'approved', is_active = 1, rejection_reason = NULL, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$adminUserId, $id]);
    }

    public static function reject(int $id, int $adminUserId, ?string $reason = null): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE fee_categories SET approval_status = 'rejected', is_active = 0, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$reason ?: 'Rejected by Administrator', $adminUserId, $id]);
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();
        $isVar = !empty($data['is_variable']) ? 1 : 0;
        if ($isVar) {
            $db->prepare('UPDATE fee_categories SET is_variable = 0 WHERE id != ?')->execute([$id]);
        }
        $stmt = $db->prepare(
            'UPDATE fee_categories SET name = ?, default_amount = ?, is_variable = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $isVar ? 0.00 : (float) ($data['default_amount'] ?? 0),
            $isVar,
            (int) ($data['sort_order'] ?? 99),
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $db = Database::getInstance();

        // Check if any tuition fee items reference this category
        $check = $db->prepare('SELECT COUNT(*) AS cnt FROM tuition_fee_items WHERE fee_category_id = ?');
        $check->execute([$id]);
        $cnt = (int) ($check->fetch()['cnt'] ?? 0);
        if ($cnt > 0) {
            throw new \RuntimeException(
                'Cannot delete this fee category because it is used in ' . $cnt . ' existing tuition assessment(s). Remove or reassign those assessments first.'
            );
        }

        $stmt = $db->prepare('DELETE FROM fee_categories WHERE id = ?');
        $stmt->execute([$id]);
    }
}
