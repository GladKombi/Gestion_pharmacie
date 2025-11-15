<?php
require_once '../../connexion/connexion.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de vente manquant']);
    exit;
}

$saleId = (int)$_GET['id'];

try {
    // Récupérer les informations de la vente
    $stmt = $pdo->prepare("
        SELECT s.*, 
               SUM(si.quantity * si.unit_price) as total_amount,
               COUNT(si.id) as items_count
        FROM sales s 
        LEFT JOIN sale_items si ON s.id = si.sale_id 
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
        exit;
    }

    // Récupérer les articles de la vente
    $stmt = $pdo->prepare("
        SELECT si.*, p.name as product_name, s.price as cost_price
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        LEFT JOIN stock s ON si.stock_id = s.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    $stmt->execute([$saleId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la marge totale
    $totalMargin = 0;
    $totalCost = 0;
    foreach ($items as $item) {
        $itemCost = $item['cost_price'] * $item['quantity'];
        $itemRevenue = $item['quantity'] * $item['unit_price'];
        $totalCost += $itemCost;
        $totalMargin += ($itemRevenue - $itemCost);
    }

    $marginPercentage = $totalCost > 0 ? ($totalMargin / $totalCost * 100) : 0;

    // Générer le HTML pour les détails
    $html = '
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h4 class="font-semibold text-gray-700">Informations de la vente</h4>
                <p><span class="font-medium">N°:</span> #' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT) . '</p>
                <p><span class="font-medium">Date:</span> ' . date('d/m/Y H:i', strtotime($sale['sale_date'])) . '</p>
                <p><span class="font-medium">Client:</span> ' . ($sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'Non renseigné') . '</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-700">Statistiques</h4>
                <p><span class="font-medium">Nombre d\'articles:</span> ' . $sale['items_count'] . '</p>
                <p><span class="font-medium">Montant total:</span> ' . number_format($sale['total_amount'], 2, ',', ' ') . ' €</p>
                <p><span class="font-medium">Marge totale:</span> ' . number_format($totalMargin, 2, ',', ' ') . ' € (' . number_format($marginPercentage, 1, ',', ' ') . '%)</p>
                <p><span class="font-medium">Statut:</span> ' . ($sale['statut'] == 1 ? '<span class="text-green-600 font-semibold">Complétée</span>' : '<span class="text-red-600 font-semibold">Annulée</span>') . '</p>
            </div>
        </div>

        <div>
            <h4 class="font-semibold text-gray-700 mb-3">Articles vendus</h4>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Quantité</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix Unitaire</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Coût Unitaire</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Marge</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $itemCost = $item['cost_price'] * $item['quantity'];
        $itemMargin = $itemTotal - $itemCost;
        $marginPercent = $item['cost_price'] > 0 ? (($item['unit_price'] - $item['cost_price']) / $item['cost_price'] * 100) : 0;
        
        $marginClass = $itemMargin >= 0 ? 'text-green-600' : 'text-red-600';
        $marginSign = $itemMargin >= 0 ? '+' : '';
        
        $html .= '
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap">' . htmlspecialchars($item['product_name']) . '</td>
                        <td class="px-4 py-2 whitespace-nowrap text-center">' . $item['quantity'] . '</td>
                        <td class="px-4 py-2 whitespace-nowrap text-right">' . number_format($item['unit_price'], 2, ',', ' ') . ' €</td>
                        <td class="px-4 py-2 whitespace-nowrap text-right">' . number_format($item['cost_price'], 2, ',', ' ') . ' €</td>
                        <td class="px-4 py-2 whitespace-nowrap text-right ' . $marginClass . '">
                            ' . $marginSign . number_format($itemMargin, 2, ',', ' ') . ' € (' . $marginSign . number_format($marginPercent, 1, ',', ' ') . '%)
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-right font-semibold">' . number_format($itemTotal, 2, ',', ' ') . ' €</td>
                    </tr>';
    }

    $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-end pt-4 border-t border-gray-200">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 print-invoice-details" data-id="' . $saleId . '">
                <i class="material-symbols-rounded text-base mr-2">print</i>
                Imprimer la Facture
            </button>
        </div>
    </div>';

    echo json_encode(['success' => true, 'html' => $html]);

} catch (PDOException $e) {
    error_log("Erreur get-sale-details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
}
?>