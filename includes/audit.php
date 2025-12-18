<?php

function auditLog(
    $conn,
    ?int $userId,
    string $userRole,
    string $actionType,
    string $entityName,
    ?int $entityId,
    ?string $actionDetails = null
) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "
        INSERT INTO AuditLogs
        (user_id, user_role, action_type, entity_name, entity_id, action_details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $params = [
        $userId,
        $userRole,
        $actionType,
        $entityName,
        $entityId,
        $actionDetails,
        $ipAddress
    ];

    sqlsrv_query($conn, $sql, $params);
}
