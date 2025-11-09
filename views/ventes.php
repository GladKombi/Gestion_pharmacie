<?php
// Configuration de la base de données
require_once '../connexion/connexion.php';
require_once '../includes/functions.php';

// Vérifier si la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Paramètres de recherche
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? validateInput($_GET['date_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? validateInput($_GET['status_filter']) : '';

// Récupérer les ventes avec pagination
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(customer_name LIKE ? OR id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(sale_date) = ?";
    $params[] = $date_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "statut = ?";
    $params[] = $status_filter;
}

$where_sql = implode(" AND ", $where_conditions);

// Requête pour les ventes avec pagination
$sql = "
    SELECT s.*, 
           COUNT(si.id) as items_count,
           SUM(si.quantity * si.unit_price) as total_amount
    FROM sales s 
    LEFT JOIN sale_items si ON s.id = si.sale_id 
    WHERE $where_sql
    GROUP BY s.id
    ORDER BY s.sale_date DESC, s.id DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter le total des ventes pour la pagination
$count_sql = "
    SELECT COUNT(DISTINCT s.id) 
    FROM sales s 
    LEFT JOIN sale_items si ON s.id = si.sale_id 
    WHERE $where_sql
";

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_sales = $stmt->fetchColumn();
$total_pages = ceil($total_sales / $limit);

// Calculer les statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sales,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_sale
    FROM (
        SELECT s.id, SUM(si.quantity * si.unit_price) as total_amount
        FROM sales s 
        JOIN sale_items si ON s.id = si.sale_id 
        WHERE s.statut = 1
        GROUP BY s.id
    ) as sales_totals
");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_sales_count = $stats['total_sales'] ?? 0;
$total_revenue = $stats['total_revenue'] ?? 0;
$average_sale = $stats['average_sale'] ?? 0;

// Récupérer les produits et stocks pour le modal avec les coûts d'achat
$products_stmt = $pdo->prepare("
    SELECT p.id, p.name, 
           s.id as stock_id, s.current_quantity, s.expiry_date, s.price as cost_price
    FROM products p 
    LEFT JOIN stock s ON p.id = s.product_id AND s.statut = 1 AND s.current_quantity > 0
    WHERE p.statut = 1
    ORDER BY p.name, s.expiry_date ASC
");
$products_stmt->execute();
$products_data = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les produits avec leurs stocks
$products = [];
foreach ($products_data as $row) {
    $product_id = $row['id'];
    if (!isset($products[$product_id])) {
        $products[$product_id] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'stocks' => []
        ];
    }
    
    if ($row['stock_id']) {
        $products[$product_id]['stocks'][] = [
            'stock_id' => $row['stock_id'],
            'current_quantity' => $row['current_quantity'],
            'expiry_date' => $row['expiry_date'],
            'cost_price' => $row['cost_price']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>FOAMIS Sarl - Gestion des Ventes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .material-symbols-rounded {
            opacity: 0.7;
        }

        .sidebar-mobile {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .sidebar-mobile.open {
            transform: translateX(0);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
        }

        .sidebar-overlay.open {
            display: block;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem;
            }

            .sidebar-mobile {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none !important;
            }
        }

        .pharma-primary {
            background: linear-gradient(135deg, #10b981, #047857);
        }

        .pharma-secondary {
            background: linear-gradient(135deg, #059669, #065f46);
        }

        .dashboard-container {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .pagination-active {
            background-color: #10b981;
            color: white;
        }

        .delete-modal {
            max-width: 500px;
        }

        .cart-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .stock-option {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .expired-stock {
            color: #ef4444;
            font-weight: bold;
        }

        .expiring-soon-stock {
            color: #f59e0b;
            font-weight: bold;
        }

        .price-warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border-radius: 0.375rem;
        }

        .price-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border-radius: 0.375rem;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Overlay pour mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidenav-main" class="fixed top-0 left-0 h-full w-64 bg-white shadow-xl z-50 sidebar-mobile lg:translate-x-0">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <a class="flex items-center space-x-2" href="#">
                <div class="w-8 h-8 pharma-primary rounded flex items-center justify-center">
                    <span class="text-white text-xs font-bold">FS</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">FOAMIS Sarl</span>
            </a>
            <button id="close-sidebar" class="text-gray-500 lg:hidden">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <div class="overflow-y-auto h-[calc(100%-10rem)] p-2">
            <ul class="space-y-1">
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="../index.php">
                        <i class="material-symbols-rounded text-base">dashboard</i>
                        <span class="ml-3">Tableau de Bord</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="../produits/index.php">
                        <i class="material-symbols-rounded text-base">inventory_2</i>
                        <span class="ml-3">Gestion des Produits</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="../stock/index.php">
                        <i class="material-symbols-rounded text-base">warehouse</i>
                        <span class="ml-3">Gestion des Stocks</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-semibold text-white pharma-primary rounded-lg shadow-md" href="#">
                        <i class="material-symbols-rounded text-base">shopping_cart</i>
                        <span class="ml-3">Ventes & Caisse</span>
                    </a>
                </li>
            </ul>

            <h6 class="px-4 pt-3 mt-4 text-xs font-bold text-gray-500 uppercase">Configuration</h6>

            <ul class="space-y-1 mt-2">
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="../categories/index.php">
                        <i class="material-symbols-rounded text-base">category</i>
                        <span class="ml-3">Catégories</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="absolute bottom-0 w-full p-4 border-t border-gray-200 bg-white">
            <a class="block w-full text-center py-2 mb-2 text-sm font-semibold text-green-600 border border-green-600 rounded-lg hover:bg-green-50" href="#" type="button">Aide & Support</a>
            <a class="block w-full text-center py-2 text-sm font-semibold text-white pharma-primary rounded-lg hover:opacity-90" href="#" type="button">Déconnexion</a>
        </div>
    </aside>

    <main class="main-content flex-grow w-full transition-all duration-300 p-4 lg:p-6 lg:ml-64">
        <nav class="bg-white shadow-md rounded-xl p-4 mb-4">
            <div class="flex justify-between items-center">
                <nav aria-label="breadcrumb">
                    <ol class="flex text-gray-500 text-sm">
                        <li><a href="../index.php" class="opacity-80">Gestion</a></li>
                        <li class="px-2">/</li>
                        <li class="font-bold text-gray-800">Ventes & Caisse</li>
                    </ol>
                </nav>

                <div class="flex items-center space-x-4">
                    <form method="GET" class="relative hidden sm:block">
                        <input type="text" name="search" placeholder="Rechercher une vente..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="py-2 pl-4 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </form>

                    <button id="toggle-sidebar" class="text-gray-600 hover:text-gray-900 lg:hidden">
                        <i class="material-symbols-rounded text-lg">menu</i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Contenu principal -->
        <div class="dashboard-container">
            <!-- Messages de notification -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['message']['type'] == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <!-- En-tête avec boutons d'action -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestion des Ventes - FOAMIS Sarl</h1>
                    <p class="text-gray-600">Gérez vos ventes et transactions</p>
                </div>
                <div class="flex gap-3">
                    <button id="open-sale-modal" class="px-4 py-2 pharma-primary text-white rounded-lg hover:opacity-90 flex items-center">
                        <i class="material-symbols-rounded text-base mr-2">add</i>
                        Nouvelle Vente
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-green-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Ventes</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_sales_count; ?></h3>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="material-symbols-rounded text-green-600">receipt</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-blue-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Chiffre d'Affaires</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_revenue, 2, ',', ' '); ?> €</h3>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="material-symbols-rounded text-blue-600">euro</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-purple-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Panier Moyen</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($average_sale, 2, ',', ' '); ?> €</h3>
                        </div>
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="material-symbols-rounded text-purple-600">shopping_cart</i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white shadow-md rounded-xl p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Rechercher par client ou numéro de vente..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="status_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Tous les statuts</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Complétée</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 pharma-primary text-white rounded-lg hover:opacity-90">
                            <i class="material-symbols-rounded text-base mr-2">search</i>
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des ventes -->
            <div class="bg-white shadow-md rounded-xl overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Historique des Ventes
                        <?php if ($total_sales > 0): ?>
                            <span class="text-sm text-gray-500 font-normal">(<?php echo $total_sales; ?> vente(s) trouvée(s))</span>
                        <?php endif; ?>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <?php if (empty($sales)): ?>
                        <div class="text-center py-8">
                            <i class="material-symbols-rounded text-gray-400 text-6xl mb-4">receipt</i>
                            <p class="text-gray-500">Aucune vente trouvée</p>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Vente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Articles</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($sales as $sale): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($sale['customer_name'] ?: 'Client non renseigné'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $sale['items_count']; ?> article(s)
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <?php echo number_format($sale['total_amount'], 2, ',', ' '); ?> €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($sale['statut'] == 1): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                    Complétée
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                    Annulée
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-green-600 hover:text-green-900 mr-3 view-sale"
                                                data-id="<?php echo $sale['id']; ?>">
                                                Détails
                                            </button>
                                            <?php if ($sale['statut'] == 1): ?>
                                                <button class="text-red-600 hover:text-red-900 cancel-sale-btn" 
                                                        data-id="<?php echo $sale['id']; ?>"
                                                        data-customer_name="<?php echo htmlspecialchars($sale['customer_name']); ?>">
                                                    Annuler
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo buildQueryString(['page' => $page - 1]); ?>" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?<?php echo buildQueryString(['page' => $i]); ?>"
                                        class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 <?php echo $i == $page ? 'pagination-active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo buildQueryString(['page' => $page + 1]); ?>" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nouvelle Vente -->
    <div id="sale-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Nouvelle Vente</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>

            <div class="p-6">
                <form id="sale-form" method="POST" action="../models/traitement/sale-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_sale" value="ajouter">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">

                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de vente *</label>
                                <input type="datetime-local" name="sale_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du client</label>
                                <input type="text" name="customer_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                                       placeholder="Nom du client (optionnel)">
                            </div>
                        </div>
                    </div>

                    <!-- Panier -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Panier</h4>
                        
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-12 gap-4 mb-2 text-sm font-medium text-gray-700">
                                <div class="col-span-4">Produit</div>
                                <div class="col-span-2">Stock</div>
                                <div class="col-span-2">Quantité</div>
                                <div class="col-span-2">Prix Unitaire</div>
                                <div class="col-span-2">Total</div>
                            </div>
                            
                            <div id="cart-items">
                                <!-- Les articles du panier seront ajoutés ici dynamiquement -->
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <button type="button" id="add-cart-item" class="px-4 py-2 text-sm font-medium text-green-600 border border-green-600 rounded-lg hover:bg-green-50 flex items-center">
                                    <i class="material-symbols-rounded text-base mr-2">add</i>
                                    Ajouter un article
                                </button>
                                
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-gray-900">
                                        Total: <span id="cart-total">0.00</span> €
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white pharma-primary rounded-lg hover:opacity-90">
                            Enregistrer la Vente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Détails Vente -->
    <div id="sale-details-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Détails de la Vente</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>
            <div class="p-6">
                <div id="sale-details-content">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'annulation -->
    <div id="cancel-confirm-modal" class="modal">
        <div class="modal-content delete-modal">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Confirmation d'annulation</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="material-symbols-rounded text-red-600 text-2xl">warning</i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Êtes-vous sûr de vouloir annuler cette vente ?</h4>
                    <p class="text-gray-600 mb-4">La vente pour "<span id="sale-to-cancel-name" class="font-semibold"></span>" sera annulée.</p>
                    <p class="text-sm text-red-600 mb-6">Les stocks seront réapprovisionnés.</p>
                </div>
                
                <form id="cancel-sale-form" method="POST" action="../models/traitement/sale-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_sale" value="annuler">
                    <input type="hidden" name="id" id="sale-to-cancel-id">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 flex items-center">
                            <i class="material-symbols-rounded text-base mr-2">cancel</i>
                            Annuler la Vente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Gestion de la sidebar
        const sidebar = document.getElementById('sidenav-main');
        const overlay = document.getElementById('sidebar-overlay');
        const toggleButton = document.getElementById('toggle-sidebar');
        const closeButton = document.getElementById('close-sidebar');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('open');
        });

        closeButton.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });

        // Données des produits
        const products = <?php echo json_encode($products); ?>;

        // Gestion des modales
        const saleModal = document.getElementById('sale-modal');
        const saleDetailsModal = document.getElementById('sale-details-modal');
        const cancelConfirmModal = document.getElementById('cancel-confirm-modal');
        const openSaleModalBtn = document.getElementById('open-sale-modal');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const viewSaleBtns = document.querySelectorAll('.view-sale');
        const cancelSaleBtns = document.querySelectorAll('.cancel-sale-btn');

        // Variables du panier
        let cartItems = [];
        let cartItemCounter = 0;

        // Fonction pour mettre à jour le total du panier
        function updateCartTotal() {
            let total = 0;
            cartItems.forEach(item => {
                total += item.quantity * item.unitPrice;
            });
            document.getElementById('cart-total').textContent = total.toFixed(2);
        }

        // Fonction pour créer un sélecteur de produit
        function createProductSelect(selectedProductId = '') {
            const select = document.createElement('select');
            select.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 product-select';
            select.innerHTML = '<option value="">Sélectionner un produit</option>';
            
            Object.values(products).forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.name;
                option.selected = product.id == selectedProductId;
                select.appendChild(option);
            });
            
            return select;
        }

        // Fonction pour créer un sélecteur de stock
        function createStockSelect(productId, selectedStockId = '') {
            const select = document.createElement('select');
            select.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 stock-select';
            select.innerHTML = '<option value="">Sélectionner un stock</option>';
            
            if (productId && products[productId]) {
                products[productId].stocks.forEach(stock => {
                    const option = document.createElement('option');
                    option.value = stock.stock_id;
                    
                    const today = new Date();
                    const expiryDate = new Date(stock.expiry_date);
                    let statusClass = '';
                    
                    if (expiryDate < today) {
                        statusClass = 'expired-stock';
                    } else if ((expiryDate - today) / (1000 * 60 * 60 * 24) <= 30) {
                        statusClass = 'expiring-soon-stock';
                    }
                    
                    option.innerHTML = `${stock.current_quantity} unité(s) - Exp: ${new Date(stock.expiry_date).toLocaleDateString('fr-FR')} <span class="stock-option ${statusClass}">${statusClass ? '⚠️' : ''}</span>`;
                    option.setAttribute('data-max-quantity', stock.current_quantity);
                    option.setAttribute('data-cost-price', stock.cost_price);
                    option.selected = stock.stock_id == selectedStockId;
                    select.appendChild(option);
                });
            }
            
            return select;
        }

        // Fonction pour créer un message d'alerte prix
        function createPriceAlert(costPrice, currentPrice) {
            const alertDiv = document.createElement('div');
            const margin = ((currentPrice - costPrice) / costPrice * 100).toFixed(1);
            
            if (currentPrice < costPrice) {
                alertDiv.className = 'price-error';
                alertDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="material-symbols-rounded text-red-600 mr-2">error</i>
                        <span class="text-sm font-medium text-red-800">
                            ⚠️ Prix de vente inférieur au coût d'achat (${costPrice.toFixed(2)} €)
                        </span>
                    </div>
                    <p class="text-xs text-red-600 mt-1">
                        Perte: ${(costPrice - currentPrice).toFixed(2)} € (${Math.abs(margin)}%)
                    </p>
                `;
            } else if (currentPrice < costPrice * 1.1) {
                alertDiv.className = 'price-warning';
                alertDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="material-symbols-rounded text-yellow-600 mr-2">warning</i>
                        <span class="text-sm font-medium text-yellow-800">
                            Marge faible: ${margin}%
                        </span>
                    </div>
                    <p class="text-xs text-yellow-600 mt-1">
                        Coût: ${costPrice.toFixed(2)} € | Prix actuel: ${currentPrice.toFixed(2)} €
                    </p>
                `;
            } else {
                alertDiv.className = 'hidden';
            }
            
            return alertDiv;
        }

        // Fonction pour ajouter un article au panier
        function addCartItem(productId = '', stockId = '', quantity = 1, unitPrice = 0) {
            const cartItemsContainer = document.getElementById('cart-items');
            const itemId = cartItemCounter++;
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item grid grid-cols-12 gap-4 items-start';
            itemDiv.innerHTML = `
                <div class="col-span-4 product-select-container"></div>
                <div class="col-span-2 stock-select-container"></div>
                <div class="col-span-2">
                    <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 quantity-input" min="1" value="${quantity}">
                </div>
                <div class="col-span-2">
                    <input type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 price-input" min="0" value="${unitPrice}">
                    <div class="price-alert-container mt-2"></div>
                </div>
                <div class="col-span-2 flex items-start justify-between">
                    <div>
                        <span class="item-total font-semibold block">${(quantity * unitPrice).toFixed(2)} €</span>
                        <span class="item-margin text-xs text-gray-500 block mt-1"></span>
                    </div>
                    <button type="button" class="text-red-600 hover:text-red-900 remove-item ml-2">
                        <i class="material-symbols-rounded text-base">delete</i>
                    </button>
                </div>
                <input type="hidden" name="cart_items[${itemId}][product_id]" class="product-id-input" value="${productId}">
                <input type="hidden" name="cart_items[${itemId}][stock_id]" class="stock-id-input" value="${stockId}">
                <input type="hidden" name="cart_items[${itemId}][quantity]" class="quantity-hidden-input" value="${quantity}">
                <input type="hidden" name="cart_items[${itemId}][unit_price]" class="price-hidden-input" value="${unitPrice}">
            `;
            
            cartItemsContainer.appendChild(itemDiv);
            
            // Ajouter les sélecteurs
            const productSelectContainer = itemDiv.querySelector('.product-select-container');
            const stockSelectContainer = itemDiv.querySelector('.stock-select-container');
            const priceAlertContainer = itemDiv.querySelector('.price-alert-container');
            
            productSelectContainer.appendChild(createProductSelect(productId));
            stockSelectContainer.appendChild(createStockSelect(productId, stockId));
            
            // Événements
            const productSelect = productSelectContainer.querySelector('select');
            const stockSelect = stockSelectContainer.querySelector('select');
            const quantityInput = itemDiv.querySelector('.quantity-input');
            const priceInput = itemDiv.querySelector('.price-input');
            const removeBtn = itemDiv.querySelector('.remove-item');
            const itemTotal = itemDiv.querySelector('.item-total');
            const itemMargin = itemDiv.querySelector('.item-margin');
            const quantityHiddenInput = itemDiv.querySelector('.quantity-hidden-input');
            const priceHiddenInput = itemDiv.querySelector('.price-hidden-input');
            const productIdInput = itemDiv.querySelector('.product-id-input');
            const stockIdInput = itemDiv.querySelector('.stock-id-input');
            
            let currentCostPrice = 0;
            
            // Fonction pour mettre à jour les alertes de prix
            function updatePriceAlerts() {
                const currentPrice = parseFloat(priceInput.value) || 0;
                
                // Vider le conteneur d'alertes
                priceAlertContainer.innerHTML = '';
                
                if (currentCostPrice > 0) {
                    const alert = createPriceAlert(currentCostPrice, currentPrice);
                    priceAlertContainer.appendChild(alert);
                    
                    // Calculer et afficher la marge
                    const margin = currentPrice - currentCostPrice;
                    const marginPercent = ((margin / currentCostPrice) * 100).toFixed(1);
                    
                    if (margin >= 0) {
                        itemMargin.textContent = `+${marginPercent}%`;
                        itemMargin.className = 'item-margin text-xs text-green-600 block mt-1';
                    } else {
                        itemMargin.textContent = `${marginPercent}%`;
                        itemMargin.className = 'item-margin text-xs text-red-600 block mt-1';
                    }
                } else {
                    itemMargin.textContent = '';
                }
            }
            
            // Événement changement de produit
            productSelect.addEventListener('change', function() {
                const selectedProductId = this.value;
                productIdInput.value = selectedProductId;
                
                // Mettre à jour le sélecteur de stock
                stockSelectContainer.innerHTML = '';
                stockSelectContainer.appendChild(createStockSelect(selectedProductId));
                
                const newStockSelect = stockSelectContainer.querySelector('select');
                newStockSelect.addEventListener('change', handleStockChange);
                
                updateItemTotal();
            });
            
            // Événement changement de stock
            stockSelect.addEventListener('change', handleStockChange);
            
            function handleStockChange() {
                const selectedStockId = this.value;
                stockIdInput.value = selectedStockId;
                
                // Mettre à jour la quantité maximale et le coût d'achat
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const maxQuantity = parseInt(selectedOption.getAttribute('data-max-quantity'));
                    currentCostPrice = parseFloat(selectedOption.getAttribute('data-cost-price'));
                    
                    quantityInput.max = maxQuantity;
                    if (quantityInput.value > maxQuantity) {
                        quantityInput.value = maxQuantity;
                        quantityHiddenInput.value = maxQuantity;
                    }
                    
                    // Définir le prix minimum suggéré
                    if (currentCostPrice > 0) {
                        priceInput.min = currentCostPrice;
                        // Si le prix actuel est inférieur au coût, le mettre à jour
                        if (parseFloat(priceInput.value) < currentCostPrice) {
                            priceInput.value = currentCostPrice.toFixed(2);
                            priceHiddenInput.value = currentCostPrice.toFixed(2);
                        }
                    }
                }
                
                updatePriceAlerts();
                updateItemTotal();
            }
            
            // Événement changement de quantité
            quantityInput.addEventListener('input', function() {
                const maxQuantity = parseInt(this.max) || Infinity;
                if (this.value > maxQuantity) {
                    this.value = maxQuantity;
                }
                quantityHiddenInput.value = this.value;
                updateItemTotal();
            });
            
            // Événement changement de prix
            priceInput.addEventListener('input', function() {
                const price = parseFloat(this.value) || 0;
                
                // Empêcher la vente en dessous du coût
                if (currentCostPrice > 0 && price < currentCostPrice) {
                    this.classList.add('border-red-500', 'bg-red-50');
                } else {
                    this.classList.remove('border-red-500', 'bg-red-50');
                }
                
                priceHiddenInput.value = this.value;
                updatePriceAlerts();
                updateItemTotal();
            });
            
            // Événement suppression
            removeBtn.addEventListener('click', function() {
                itemDiv.remove();
                updateCartTotal();
            });
            
            function updateItemTotal() {
                const total = (parseFloat(quantityInput.value) || 0) * (parseFloat(priceInput.value) || 0);
                itemTotal.textContent = total.toFixed(2) + ' €';
                updateCartTotal();
            }
            
            // Initialiser les alertes de prix
            if (stockId) {
                const selectedOption = stockSelect.options[stockSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    currentCostPrice = parseFloat(selectedOption.getAttribute('data-cost-price')) || 0;
                }
            }
            updatePriceAlerts();
            
            // Ajouter au tableau cartItems
            cartItems.push({
                itemId: itemId,
                productId: productId,
                stockId: stockId,
                quantity: quantity,
                unitPrice: unitPrice
            });
        }

        // Ouvrir modal vente
        openSaleModalBtn.addEventListener('click', () => {
            saleModal.classList.add('open');
            // Ajouter un article vide au panier
            document.getElementById('cart-items').innerHTML = '';
            cartItems = [];
            addCartItem();
        });

        // Fermer modales
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                saleModal.classList.remove('open');
                saleDetailsModal.classList.remove('open');
                cancelConfirmModal.classList.remove('open');
            });
        });

        // Ajouter un article au panier
        document.getElementById('add-cart-item').addEventListener('click', () => {
            addCartItem();
        });

        // Voir les détails d'une vente
        viewSaleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const saleId = btn.getAttribute('data-id');
                
                fetch(`../models/select/get-sale-details.php?id=${saleId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('sale-details-content').innerHTML = data.html;
                            saleDetailsModal.classList.add('open');
                        } else {
                            Toastify({
                                text: "Erreur lors du chargement des détails",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: "linear-gradient(to right, #ef4444, #dc2626)",
                                },
                            }).showToast();
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        Toastify({
                            text: "Erreur lors du chargement des détails",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: {
                                background: "linear-gradient(to right, #ef4444, #dc2626)",
                            },
                        }).showToast();
                    });
            });
        });

        // Annuler une vente
        cancelSaleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const saleId = btn.getAttribute('data-id');
                const customerName = btn.getAttribute('data-customer_name');
                
                document.getElementById('sale-to-cancel-id').value = saleId;
                document.getElementById('sale-to-cancel-name').textContent = customerName;
                
                cancelConfirmModal.classList.add('open');
            });
        });

        // Validation du formulaire de vente
        document.getElementById('sale-form').addEventListener('submit', function(e) {
            const cartItems = document.querySelectorAll('.cart-item');
            let isValid = true;
            let errorMessage = '';
            let hasPriceError = false;
            
            if (cartItems.length === 0) {
                isValid = false;
                errorMessage = "Veuillez ajouter au moins un article au panier";
            }
            
            cartItems.forEach(item => {
                const productSelect = item.querySelector('.product-select');
                const stockSelect = item.querySelector('.stock-select');
                const quantityInput = item.querySelector('.quantity-input');
                const priceInput = item.querySelector('.price-input');
                const priceAlert = item.querySelector('.price-error');
                
                if (!productSelect.value) {
                    isValid = false;
                    errorMessage = "Veuillez sélectionner un produit pour tous les articles";
                }
                
                if (!stockSelect.value) {
                    isValid = false;
                    errorMessage = "Veuillez sélectionner un stock pour tous les articles";
                }
                
                if (!quantityInput.value || quantityInput.value <= 0) {
                    isValid = false;
                    errorMessage = "Veuillez saisir une quantité valide pour tous les articles";
                }
                
                if (!priceInput.value || priceInput.value <= 0) {
                    isValid = false;
                    errorMessage = "Veuillez saisir un prix valide pour tous les articles";
                }
                
                // Vérifier que la quantité ne dépasse pas le stock disponible
                const maxQuantity = parseInt(stockSelect.options[stockSelect.selectedIndex]?.getAttribute('data-max-quantity')) || 0;
                if (parseInt(quantityInput.value) > maxQuantity) {
                    isValid = false;
                    errorMessage = `La quantité demandée dépasse le stock disponible (${maxQuantity} unités)`;
                }
                
                // Vérifier les prix inférieurs au coût
                if (priceAlert) {
                    hasPriceError = true;
                }
            });
            
            if (hasPriceError) {
                isValid = false;
                errorMessage = "Certains articles sont vendus en dessous du coût d'achat. Veuillez corriger les prix.";
            }
            
            if (!isValid) {
                e.preventDefault();
                Toastify({
                    text: errorMessage,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: "linear-gradient(to right, #ef4444, #dc2626)",
                    },
                }).showToast();
            }
        });

        // Fermer modales en cliquant en dehors
        window.addEventListener('click', (e) => {
            if (e.target === saleModal) {
                saleModal.classList.remove('open');
            }
            if (e.target === saleDetailsModal) {
                saleDetailsModal.classList.remove('open');
            }
            if (e.target === cancelConfirmModal) {
                cancelConfirmModal.classList.remove('open');
            }
        });

        // Vérifie si un message de session est présent
        <?php if (isset($_SESSION['message'])): ?>
            Toastify({
                text: "<?= htmlspecialchars($_SESSION['message']['text']) ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: "linear-gradient(to right, <?= ($_SESSION['message']['type'] == 'success') ? '#22c55e, #16a34a' : '#ef4444, #dc2626' ?>)",
                },
            }).showToast();

            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>