<?php
require_once '../../connexion/connexion.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers pour éviter les problèmes de cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Log pour débogage
error_log("get-invoice-data.php appelé avec ID: " . ($_GET['id'] ?? 'NULL'));

if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("ID manquant dans la requête");
    echo json_encode(['success' => false, 'message' => 'ID de vente manquant']);
    exit;
}

$saleId = (int)$_GET['id'];
error_log("Traitement de la vente ID: " . $saleId);

try {
    // Vérifier la connexion à la base de données
    if (!$pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }

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
    
    if (!$stmt->execute([$saleId])) {
        throw new Exception('Erreur lors de l exécution de la requête vente');
    }
    
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        error_log("Vente non trouvée pour ID: " . $saleId);
        echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
        exit;
    }

    error_log("Vente trouvée: " . $sale['id']);

    // Récupérer les articles de la vente
    $stmt = $pdo->prepare("
        SELECT si.*, p.name as product_name
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    
    if (!$stmt->execute([$saleId])) {
        throw new Exception('Erreur lors de l exécution de la requête articles');
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Articles trouvés: " . count($items));

    // Générer le HTML de la facture
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Facture #' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT) . ' - FOAMIS Sarl</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                color: #333;
                background: white;
            }
            .invoice-container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: white;
            }
            .invoice-header { 
                border-bottom: 2px solid #10b981; 
                padding-bottom: 20px; 
                margin-bottom: 20px;
            }
            .invoice-header h1 { 
                color: #10b981; 
                margin: 0 0 5px 0;
                font-size: 28px;
            }
            .invoice-header h2 { 
                color: #047857; 
                margin: 0;
                font-size: 24px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0;
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 12px 8px; 
                text-align: left;
            }
            th { 
                background-color: #f8f9fa; 
                font-weight: bold;
            }
            tfoot td { 
                font-weight: bold; 
                background-color: #f8f9fa;
            }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .footer { 
                margin-top: 40px; 
                padding-top: 20px; 
                border-top: 1px solid #ddd;
                font-size: 12px;
                color: #666;
            }
            @media print {
                body { margin: 0; padding: 0; }
                .invoice-container { max-width: none; margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <!-- En-tête de la facture -->
            <div class="invoice-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1>FOAMIS Sarl</h1>
                        <p style="margin: 2px 0; color: #666;">Pharmacie et Parapharmacie</p>
                        <p style="margin: 2px 0; color: #666;">123 Avenue de la Santé, 75000 Paris</p>
                        <p style="margin: 2px 0; color: #666;">Tél: 01 23 45 67 89</p>
                        <p style="margin: 2px 0; color: #666;">SIRET: 123 456 789 00012</p>
                    </div>
                    <div style="text-align: right;">
                        <h2>FACTURE</h2>
                        <p style="margin: 5px 0; color: #666;"><strong>N°:</strong> #' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT) . '</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($sale['sale_date'])) . '</p>
                    </div>
                </div>
            </div>

            <!-- Informations client -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #333; margin-bottom: 10px;">Client</h3>
                <p style="margin: 5px 0; color: #666;">' . ($sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'Client non renseigné') . '</p>
            </div>

            <!-- Détails des articles -->
            <div>
                <h3 style="color: #333; margin-bottom: 15px;">Détails de la vente</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-right">Prix Unitaire</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>';

    $total = 0;
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $total += $itemTotal;
        
        $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['product_name']) . '</td>
                            <td class="text-center">' . $item['quantity'] . '</td>
                            <td class="text-right">' . number_format($item['unit_price'], 2, ',', ' ') . ' €</td>
                            <td class="text-right">' . number_format($itemTotal, 2, ',', ' ') . ' €</td>
                        </tr>';
    }

    $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td class="text-right"><strong>' . number_format($total, 2, ',', ' ') . ' €</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Informations supplémentaires -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                    <div>
                        <h4 style="color: #333; margin-bottom: 10px;">Conditions de paiement</h4>
                        <p style="margin: 5px 0; color: #666;">Paiement comptant</p>
                        <p style="margin: 5px 0; color: #666;">Facture réglée</p>
                    </div>
                    <div>
                        <h4 style="color: #333; margin-bottom: 10px;">Remarques</h4>
                        <p style="margin: 5px 0; color: #666;">Merci pour votre confiance</p>
                        <p style="margin: 5px 0; color: #666;">À conserver pour tout échange</p>
                    </div>
                </div>
            </div>

            <!-- Pied de page -->
            <div class="footer" style="text-align: center;">
                <p>FOAMIS Sarl - Capital social: 50 000 € - APE: 4773Z</p>
                <p>Cet document a une valeur probante et doit être conservé</p>
                <p style="margin-top: 10px;">Imprimé le ' . date('d/m/Y à H:i') . '</p>
            </div>
        </div>
    </body>
    </html>';

    error_log("HTML généré avec succès, longueur: " . strlen($html));
    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    error_log("Erreur dans get-invoice-data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>