<?php
require_once 'db.php';

/**
 * Log a system event or admin action.
 * @param int|null $userId - The ID of the user performing the action
 * @param string $action - Description (e.g., "Approved user")
 * @param string|null $tableName - The table affected
 * @param int|null $rowId - The affected row ID
 * @param string|null $details - Optional details
 */
function logAction($userId, $action, $tableName = null, $rowId = null, $details = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO AuditLogs (UserID, Action, TableName, RowID, Details, IPAddress)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt->execute([$userId, $action, $tableName, $rowId, $details, $ip]);
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}
?>
