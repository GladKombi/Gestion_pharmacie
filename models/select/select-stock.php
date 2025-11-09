<?php
include '../../connexion/connexion.php';

if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    
    $stmt = $pdo->prepare("SELECT id, current_quantity FROM stock WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $existing_stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'exists' => $existing_stock !== false,
        'current_quantity' => $existing_stock ? $existing_stock['current_quantity'] : 0
    ]);
    exit();
}

echo json_encode(['exists' => false, 'current_quantity' => 0]);
?>