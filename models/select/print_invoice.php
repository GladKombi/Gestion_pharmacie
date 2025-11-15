<?php
require_once '../../connexion/connexion.php';
require_once '../../includes/functions.php'; // Assurez-vous que cette ligne est correcte

// 1. Vérifier l'ID de vente
if (!isset($_GET['sale_id']) || !is_numeric($_GET['sale_id'])) {
    header("Location: index.php"); // Redirection en cas d'ID manquant ou invalide
    exit();
}

$sale_id = (int)$_GET['sale_id'];

// 2. Requête pour récupérer les détails de la vente et des articles
try {
    // Récupérer les détails de la vente
    $sql_sale = "SELECT s.* FROM sales s WHERE s.id = ?";
    $stmt_sale = $pdo->prepare($sql_sale);
    $stmt_sale->execute([$sale_id]);
    $sale = $stmt_sale->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        // Gérer le cas où la vente n'existe pas
        die("Erreur: Vente non trouvée.");
    }

    // Récupérer les articles de la vente
    $sql_items = "
        SELECT si.*, p.name as product_name 
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$sale_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

// 3. Afficher la facture
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                font-size: 10pt;
            }
            .no-print {
                display: none !important;
            }
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="invoice-container shadow-lg my-10">
        <header class="flex justify-between items-start border-b pb-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">FACTURE</h1>
                <p class="text-sm text-gray-600">N°: **#<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?>**</p>
                <p class="text-sm text-gray-600">Date: **<?php echo date('d/m/Y H:i:s', strtotime($sale['sale_date'])); ?>**</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-green-700">FOAMIS Sarl</h2>
                <p class="text-sm text-gray-700">Votre adresse (Siège Social)</p>
                <p class="text-sm text-gray-700">Tél: +123 456 7890</p>
                <p class="text-sm text-gray-700">Email: contact@foamis.com</p>
            </div>
        </header>

        <section class="mb-6">
            <h3 class="text-base font-semibold text-gray-800 mb-2">Facturé à:</h3>
            <p class="text-lg font-medium text-gray-900">
                <?php echo htmlspecialchars($sale['customer_name'] ?: 'Client au comptoir'); ?>
            </p>
            <p class="text-sm text-gray-600">
                <?php echo htmlspecialchars($sale['customer_name'] ?: 'Contact non renseigné'); ?>
            </p>
        </section>

        <section class="mb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-green-100 border-b border-gray-300">
                        <th class="text-left py-2 px-4 text-sm font-semibold text-gray-700">Description</th>
                        <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700 w-24">Qté</th>
                        <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700 w-32">Prix Unitaire</th>
                        <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700 w-32">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_final = 0;
                    foreach ($items as $item): 
                        $item_total = $item['quantity'] * $item['unit_price'];
                        $total_final += $item_total;
                    ?>
                    <tr class="border-b border-gray-200">
                        <td class="text-left py-2 px-4 text-sm text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-right py-2 px-4 text-sm text-gray-800"><?php echo number_format($item['quantity'], 0, ',', ' '); ?></td>
                        <td class="text-right py-2 px-4 text-sm text-gray-800"><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> €</td>
                        <td class="text-right py-2 px-4 text-sm text-gray-800 font-medium"><?php echo number_format($item_total, 2, ',', ' '); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="flex justify-end mb-8">
            <div class="w-full sm:w-1/2">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Sous-Total :</span>
                    <span class="text-gray-900 font-medium"><?php echo number_format($total_final, 2, ',', ' '); ?> €</span>
                </div>
                
                <div class="flex justify-between py-3 bg-green-50 rounded-lg mt-2 px-4">
                    <span class="text-xl font-bold text-green-700">MONTANT TOTAL :</span>
                    <span class="text-xl font-bold text-green-700"><?php echo number_format($total_final, 2, ',', ' '); ?> €</span>
                </div>
            </div>
        </section>
        
        <footer class="text-center pt-6 border-t mt-6">
            <p class="text-xs text-gray-500">Merci pour votre achat ! Veuillez conserver cette facture comme preuve d'achat.</p>
            <p class="text-xs text-gray-500">Statut de la vente: **<?php echo $sale['statut'] == 1 ? 'Complétée' : 'Annulée'; ?>**</p>
        </footer>

        <div class="text-center mt-8 no-print">
            <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700">
                Imprimer
            </button>
            <button onclick="window.close()" class="px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 ml-4">
                Fermer
            </button>
        </div>
    </div>

    <script>
        // Déclencher automatiquement la boîte de dialogue d'impression
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>