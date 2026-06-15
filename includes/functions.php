<?php
/**
 * Helper Functions for Kayumanies
 */

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return 'Rp ' . number_format($price, 0, ',', '.');
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key) {
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute(array($key));
            $result = $stmt->fetch();
            return $result ? $result['setting_value'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber() {
        return 'KYM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount($userId) {
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->execute(array($userId));
            $result = $stmt->fetch();
            return $result['total'] ? $result['total'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}
?>