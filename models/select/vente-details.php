<?php
// ../models/select/get-sale-details.php
include '../../connexion/connexion.php';

if (isset($_GET['id'])) {
    $sale_id = (int)$_GET['id'];
    
    // Récupérer les informations de la vente
    $stmt = $pdo->prepare("
        SELECT s.*, 
               COUNT(si.id) as items_count,
               SUM(si.quantity * si.unit_price) as total_amount
        FROM sales s 
        LEFT JOIN sale_items si ON s.id = si.sale_id 
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sale) {
        // Récupérer les articles de la vente
        $stmt = $pdo->prepare("
            SELECT si.*, p.name as product_name
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
        $stmt->execute([$sale_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = '
            <div class="mb-6">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Numéro de vente</label>
                        <p class="text-lg font-semibold">#' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT) . '</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <p class="text-lg">' . date('d/m/Y H:i', strtotime($sale['sale_date'])) . '</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Client</label>
                    <p class="text-lg">' . ($sale['customer_name'] ?: 'Non renseigné') . '</p>
                </div>
            </div>
            
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Articles vendus</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Produit</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Quantité</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Prix Unitaire</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($items as $item) {
            $html .= '
                <tr>
                    <td class="px-4 py-2 text-sm">' . htmlspecialchars($item['product_name']) . '</td>
                    <td class="px-4 py-2 text-sm">' . $item['quantity'] . '</td>
                    <td class="px-4 py-2 text-sm">' . number_format($item['unit_price'], 2, ',', ' ') . ' €</td>
                    <td class="px-4 py-2 text-sm font-semibold">' . number_format($item['quantity'] * $item['unit_price'], 2, ',', ' ') . ' €</td>
                </tr>';
        }
        
        $html .= '
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200">
                            <td colspan="3" class="px-4 py-2 text-right text-sm font-medium">Total:</td>
                            <td class="px-4 py-2 text-sm font-semibold">' . number_format($sale['total_amount'], 2, ',', ' ') . ' €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>';
        
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID non fourni']);
}
?>