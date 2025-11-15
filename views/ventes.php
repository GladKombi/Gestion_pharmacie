<?php
// Configuration de la base de données
require_once '../connexion/connexion.php';
require_once '../includes/functions.php'; // Assurez-vous que cette fonction inclut buildQueryString si elle n'est pas dans functions.php

// Fonction utilitaire pour la pagination (à placer dans includes/functions.php si ce n'est pas déjà fait)
if (!function_exists('buildQueryString')) {
    function buildQueryString(array $new_params = [])
    {
        $current_params = $_GET;
        $params = array_merge($current_params, $new_params);
        // Supprimer les paramètres vides, sauf si la valeur est '0'
        $filtered_params = array_filter($params, function ($value, $key) {
            return $value !== '' || $key === 'status_filter';
        }, ARRAY_FILTER_USE_BOTH);
        return http_build_query($filtered_params);
    }
}


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
    $where_conditions[] = "(customer_name LIKE ? OR s.id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(sale_date) = ?";
    $params[] = $date_filter;
}

// Inclure le statut si ce n'est pas une chaîne vide (pour inclure 0)
if ($status_filter !== '') {
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
    <div id="sidebar-overlay" class="sidebar-overlay lg:hidden"></div>

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

        <div class="dashboard-container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['message']['type'] == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

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
                        <button type="submit" class="px-4 py-2 pharma-primary text-white rounded-lg hover:opacity-90 flex items-center">
                            <i class="material-symbols-rounded text-base mr-2">search</i>
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>

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
                                                <a href="../models/select/print_invoice.php?sale_id=<?php echo $sale['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    Imprimer
                                                </a>
                                            <?php endif; ?>
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
                </div>
                <div class="mt-6 border-t border-gray-200 pt-6 flex justify-end gap-3">
                    <a id="print-invoice-btn" href="#" target="_blank" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="material-symbols-rounded text-base mr-2">print</i>
                        Imprimer la Facture
                    </a>
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

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
                    <p class="text-gray-600 mb-4">La vente pour **"<span id="sale-to-cancel-name" class="font-semibold"></span>"** sera annulée et les stocks seront remis à jour.</p>
                </div>

                <form id="cancel-sale-form" method="POST" action="../models/traitement/sale-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_sale" value="annuler">
                    <input type="hidden" name="sale_id" id="sale-id-to-cancel">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">

                    <div class="mt-6 flex justify-center gap-4">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Confirmer l'Annulation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ... (Sidebar, Modal Generic Functionality) ...

            const saleModal = document.getElementById('sale-modal');
            const saleDetailsModal = document.getElementById('sale-details-modal');
            const cancelConfirmModal = document.getElementById('cancel-confirm-modal');

            document.getElementById('open-sale-modal').addEventListener('click', () => saleModal.classList.add('open'));

            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', function() {
                    saleModal.classList.remove('open');
                    saleDetailsModal.classList.remove('open');
                    cancelConfirmModal.classList.remove('open');
                });
            });

            // ----------------------------------------------------
            // GESTION DU PANIER (Logique existante de votre système)
            // ----------------------------------------------------

            const productsData = <?php echo json_encode(array_values($products)); ?>;
            let cart = [];
            let cartItemCount = 0;

            function updateCartTotal() {
                const total = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                document.getElementById('cart-total').textContent = total.toFixed(2).replace('.', ',');
            }

            function updateCartDisplay() {
                const container = document.getElementById('cart-items');
                container.innerHTML = '';

                if (cart.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">Le panier est vide.</p>';
                }

                cart.forEach((item, index) => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-4 items-center py-2 cart-item';
                    row.setAttribute('data-index', index);

                    const product = productsData.find(p => p.id === item.product_id);
                    const stock = product.stocks.find(s => s.stock_id === item.stock_id);

                    let stockInfo = '';
                    if (stock) {
                        const expiryDate = new Date(stock.expiry_date);
                        const today = new Date();
                        const diffTime = expiryDate.getTime() - today.getTime();
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        let stockClass = '';
                        if (diffDays <= 0) {
                            stockClass = 'expired-stock';
                            stockInfo = `(Exp. **${stock.expiry_date}** - Stock: ${stock.current_quantity})`;
                        } else if (diffDays <= 30) {
                            stockClass = 'expiring-soon-stock';
                            stockInfo = `(Exp. **${stock.expiry_date}** - Stock: ${stock.current_quantity})`;
                        } else {
                            stockInfo = `(Exp. ${stock.expiry_date} - Stock: ${stock.current_quantity})`;
                        }
                        stockInfo = `<span class="${stockClass}">${stockInfo}</span>`;
                    }

                    const priceWarning = stock && item.unit_price < stock.cost_price ?
                        `<div class="price-error col-span-12 text-xs">Attention: Prix de vente (${item.unit_price} €) est inférieur au prix de revient (${stock.cost_price} €)</div>` : '';

                    row.innerHTML = `
                        <div class="col-span-4 text-sm text-gray-900 font-medium">${item.product_name}</div>
                        <div class="col-span-2 text-xs text-gray-500">${stockInfo}</div>
                        <div class="col-span-2">
                            <input type="number" name="items[${index}][quantity]" value="${item.quantity}" min="1" max="${stock ? stock.current_quantity : 1}"
                                class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm text-center quantity-input" data-index="${index}" required>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[${index}][unit_price]" value="${item.unit_price}" step="0.01" min="0.01"
                                class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm text-right price-input" data-index="${index}" required>
                        </div>
                        <div class="col-span-1 text-right text-sm font-semibold text-gray-900">${(item.quantity * item.unit_price).toFixed(2).replace('.', ',')} €</div>
                        <div class="col-span-1 text-right">
                            <button type="button" class="text-red-500 hover:text-red-700 remove-item" data-index="${index}">
                                <i class="material-symbols-rounded text-base">delete</i>
                            </button>
                            <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                            <input type="hidden" name="items[${index}][stock_id]" value="${item.stock_id}">
                            <input type="hidden" name="items[${index}][cost_price]" value="${stock ? stock.cost_price : 0}">
                        </div>
                        ${priceWarning}
                    `;
                    container.appendChild(row);
                });

                attachCartListeners();
                updateCartTotal();
            }

            function attachCartListeners() {
                // Change Quantity/Price
                document.querySelectorAll('.quantity-input, .price-input').forEach(input => {
                    input.onchange = function() {
                        const index = parseInt(this.dataset.index);
                        const key = this.name.includes('quantity') ? 'quantity' : 'unit_price';
                        let value = parseFloat(this.value);

                        // Validation
                        if (key === 'quantity') {
                            const max = parseInt(this.max);
                            value = Math.max(1, Math.min(max, Math.round(value))); // Au moins 1 et max stock
                        } else {
                            value = parseFloat(value.toFixed(2));
                        }
                        this.value = value;

                        cart[index][key] = value;
                        updateCartDisplay();
                    };
                });

                // Remove Item
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.onclick = function() {
                        const index = parseInt(this.dataset.index);
                        cart.splice(index, 1);
                        updateCartDisplay();
                    };
                });
            }

            document.getElementById('add-cart-item').addEventListener('click', function() {
                const lastRow = document.createElement('div');
                lastRow.className = 'grid grid-cols-12 gap-4 items-center py-2 cart-item';

                // Créer l'option du produit
                let productOptions = '<option value="" selected disabled>Sélectionner un produit</option>';
                productsData.forEach(p => {
                    if (p.stocks.length > 0) {
                        productOptions += `<option value="${p.id}" data-stocks='${JSON.stringify(p.stocks)}'>${p.name}</option>`;
                    }
                });

                lastRow.innerHTML = `
                    <div class="col-span-4">
                        <select class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm product-select" required>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="col-span-2">
                        <select class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm stock-select" disabled required>
                            <option value="" selected disabled>Sélectionner un stock</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" value="1" min="1" class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm text-center new-quantity-input" disabled required>
                    </div>
                    <div class="col-span-2">
                        <input type="number" step="0.01" value="0.00" min="0.01" class="w-full px-2 py-1 border border-gray-300 rounded-lg text-sm text-right new-price-input" disabled required>
                    </div>
                    <div class="col-span-1 text-right text-sm font-semibold text-gray-400">0.00 €</div>
                    <div class="col-span-1 text-right">
                        <button type="button" class="text-green-500 hover:text-green-700 add-to-cart-btn" disabled>
                            <i class="material-symbols-rounded text-base">check</i>
                        </button>
                    </div>
                `;

                document.getElementById('cart-items').appendChild(lastRow);

                const productSelect = lastRow.querySelector('.product-select');
                const stockSelect = lastRow.querySelector('.stock-select');
                const quantityInput = lastRow.querySelector('.new-quantity-input');
                const priceInput = lastRow.querySelector('.new-price-input');
                const addToCartBtn = lastRow.querySelector('.add-to-cart-btn');

                // Listener pour le changement de produit
                productSelect.onchange = function() {
                    stockSelect.innerHTML = '<option value="" selected disabled>Sélectionner un stock</option>';
                    stockSelect.disabled = false;
                    const selectedOption = this.options[this.selectedIndex];
                    const stocks = JSON.parse(selectedOption.dataset.stocks);

                    stocks.forEach(s => {
                        const expiryDate = new Date(s.expiry_date);
                        const today = new Date();
                        const diffTime = expiryDate.getTime() - today.getTime();
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        let info = `Stock: ${s.current_quantity} - Exp: ${s.expiry_date}`;
                        let stockClass = '';
                        if (diffDays <= 0) {
                            stockClass = 'expired-stock';
                            info = `ATTENTION EXPIRÉ - Stock: ${s.current_quantity}`;
                        } else if (diffDays <= 30) {
                            stockClass = 'expiring-soon-stock';
                            info = `Expire bientôt - Stock: ${s.current_quantity}`;
                        }

                        const option = document.createElement('option');
                        option.value = s.stock_id;
                        option.textContent = info;
                        option.className = stockClass;
                        option.dataset.maxQuantity = s.current_quantity;
                        option.dataset.costPrice = s.cost_price;

                        stockSelect.appendChild(option);
                    });

                    priceInput.value = '0.00';
                    quantityInput.value = '1';
                    quantityInput.max = '1';
                    priceInput.disabled = true;
                    quantityInput.disabled = true;
                    addToCartBtn.disabled = true;
                };

                // Listener pour le changement de stock
                stockSelect.onchange = function() {
                    const selectedStock = this.options[this.selectedIndex];
                    const maxQuantity = parseInt(selectedStock.dataset.maxQuantity);
                    const costPrice = parseFloat(selectedStock.dataset.costPrice);

                    quantityInput.max = maxQuantity;
                    quantityInput.value = Math.min(1, maxQuantity); // Set quantity to 1 or max if max < 1
                    quantityInput.disabled = false;
                    priceInput.disabled = false;
                    addToCartBtn.disabled = false;
                };

                // Listener pour l'ajout au panier
                addToCartBtn.onclick = function() {
                    const productOption = productSelect.options[productSelect.selectedIndex];
                    const stockOption = stockSelect.options[stockSelect.selectedIndex];

                    const newItem = {
                        product_id: parseInt(productSelect.value),
                        product_name: productOption.textContent.trim(),
                        stock_id: parseInt(stockSelect.value),
                        quantity: parseInt(quantityInput.value),
                        unit_price: parseFloat(priceInput.value),
                        cost_price: parseFloat(stockOption.dataset.costPrice)
                    };

                    // Simple validation pour ne pas ajouter si les champs sont invalides (quantité/prix)
                    if (newItem.quantity > 0 && newItem.unit_price > 0) {
                        cart.push(newItem);
                        document.getElementById('cart-items').removeChild(lastRow);
                        updateCartDisplay();
                    } else {
                        alert("Veuillez entrer une quantité et un prix valides.");
                    }
                };
            });

            // ----------------------------------------------------
            // GESTION DES DÉTAILS VENTE ET ANNULATION
            // ----------------------------------------------------

            // Fonction de chargement des détails de vente (MISE À JOUR)
            document.querySelectorAll('.view-sale').forEach(button => {
                button.addEventListener('click', function() {
                    const saleId = this.dataset.id;

                    // MISE À JOUR : Mise à jour du lien d'impression dans le modal
                    const printBtn = document.getElementById('print-invoice-btn');
                    printBtn.href = `../models/select/print_invoice.php?sale_id=${saleId}`;

                    fetch(`../models/traitement/get_sale_details.php?id=${saleId}`)
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('sale-details-content').innerHTML = data;

                            // Vérifier si la vente est annulée pour masquer/afficher le bouton d'impression
                            // Le contenu de get_sale_details.php devrait fournir l'info de statut ou nous laisser utiliser l'ID
                            // Pour l'instant, on affiche le bouton, mais si vous voulez le masquer pour les annulées, 
                            // il faudrait modifier get_sale_details.php pour renvoyer le statut

                            document.getElementById('sale-details-modal').classList.add('open');
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des détails de la vente:', error);
                            Toastify({
                                text: "Erreur lors du chargement des détails de la vente.",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: "#dc2626"
                                },
                            }).showToast();
                        });
                });
            });

            // Boutons d'annulation de vente
            document.querySelectorAll('.cancel-sale-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const saleId = this.dataset.id;
                    const customerName = this.dataset.customer_name || 'Vente n°' + saleId;

                    document.getElementById('sale-id-to-cancel').value = saleId;
                    document.getElementById('sale-to-cancel-name').textContent = customerName;
                    cancelConfirmModal.classList.add('open');
                });
            });

            // ----------------------------------------------------
            // Sidebar Mobile Toggle
            // ----------------------------------------------------
            const sidebar = document.getElementById('sidenav-main');
            const overlay = document.getElementById('sidebar-overlay');
            const toggleSidebar = document.getElementById('toggle-sidebar');
            const closeSidebar = document.getElementById('close-sidebar');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.add('open');
                overlay.classList.add('open');
            });

            closeSidebar.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            });

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            });
            // ----------------------------------------------------

        });
    </script>
</body>

</html>