<?php
/**
 * EcoRise - getCampaigns.php
 * 
 * Fetches active campaigns from the database and returns them as JSON.
 * Used by the homepage AJAX call.
 */
require_once 'config.php';

header('Content-Type: application/json');

try {
    $division = $_GET['division'] ?? '';
    $district = $_GET['district'] ?? '';

    $sql = "SELECT id, title, description, division, district, image_path, target_amount, raised_amount, status FROM campaigns WHERE status = 'active' AND approval_status = 'approved'";
    $params = [];

    if (!empty($division)) {
        $sql .= " AND division = ?";
        $params[] = $division;
    }

    if (!empty($district)) {
        $sql .= " AND district = ?";
        $params[] = $district;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 12";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();

    // Prepare response
    $response = [
        'status' => 'success',
        'count' => count($campaigns),
        'campaigns' => $campaigns
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
