<?php
$brand_name = 'Kayumanies';
$brand_logo = '';

try {
    require_once __DIR__ . '/../config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name', 'store_logo')");
    foreach ($stmt->fetchAll() as $row) {
        if ($row['setting_key'] == 'store_name') $brand_name = $row['setting_value'];
        if ($row['setting_key'] == 'store_logo') $brand_logo = $row['setting_value'];
    }
} catch (Exception $e) {}
?>