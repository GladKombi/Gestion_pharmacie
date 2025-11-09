<?php
// Configuration de la base de données
require_once '../connexion/connexion.php';

// Fonctions utilitaires
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
$product_filter = isset($_GET['product_filter']) ? (int)$_GET['product_filter'] : 0;
$status_filter = isset($_GET['status_filter']) ? validateInput($_GET['status_filter']) : '';

// Récupérer tous les produits pour les filtres
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE statut = 1 ORDER BY name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construire la requête pour les stocks avec jointure sur products
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR s.current_quantity LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($product_filter > 0) {
    $where_conditions[] = "s.product_id = ?";
    $params[] = $product_filter;
}

if ($status_filter === 'low') {
    $where_conditions[] = "s.current_quantity <= s.min_quantity";
} elseif ($status_filter === 'expired') {
    $where_conditions[] = "s.expiry_date < CURDATE()";
} elseif ($status_filter === 'expiring_soon') {
    $where_conditions[] = "s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$where_sql = implode(" AND ", $where_conditions);

// Requête pour les stocks avec pagination
$sql = "
    SELECT s.*, p.name as product_name, p.photo as product_photo
    FROM stock s 
    LEFT JOIN products p ON s.product_id = p.id 
    WHERE $where_sql
    ORDER BY s.expiry_date ASC, s.current_quantity ASC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter le total des stocks pour la pagination
$count_sql = "
    SELECT COUNT(*) 
    FROM stock s 
    LEFT JOIN products p ON s.product_id = p.id 
    WHERE $where_sql
";

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_stocks = $stmt->fetchColumn();
$total_pages = ceil($total_stocks / $limit);

// Calculer les statistiques avec coûts
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(current_quantity) as total_quantity,
        SUM(current_quantity * price) as total_inventory_cost,
        SUM(CASE WHEN current_quantity <= min_quantity THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM stock 
    WHERE statut = 1
");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_stocks_stats = $stats['total'];
$total_quantity = $stats['total_quantity'];
$total_inventory_cost = $stats['total_inventory_cost'];
$low_stock_count = $stats['low_stock'];
$expired_count = $stats['expired'];
$expiring_soon_count = $stats['expiring_soon'];

// Récupérer les stocks existants par produit pour l'info dans le modal
$existingStocks = [];
$stockStmt = $pdo->prepare("SELECT product_id, COUNT(*) as stock_count FROM stock GROUP BY product_id");
$stockStmt->execute();
$stockCounts = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($stockCounts as $stockCount) {
    $existingStocks[$stockCount['product_id']] = $stockCount['stock_count'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>FOAMIS Sarl - Gestion des Stocks</title>
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
            max-width: 700px;
            width: 90%;
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

        .photo-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .pagination-active {
            background-color: #10b981;
            color: white;
        }

        .delete-modal {
            max-width: 500px;
        }

        .low-stock {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .expired {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .expiring-soon {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .expired-date {
            color: #ef4444;
            font-weight: bold;
        }

        .expiring-soon-date {
            color: #f59e0b;
            font-weight: bold;
        }

        .stock-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
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
                    <a class="flex items-center p-2 text-sm font-semibold text-white pharma-primary rounded-lg shadow-md" href="#">
                        <i class="material-symbols-rounded text-base">warehouse</i>
                        <span class="ml-3">Gestion des Stocks</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="../ventes/index.php">
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
                        <li class="font-bold text-gray-800">Stocks & Inventaire</li>
                    </ol>
                </nav>

                <div class="flex items-center space-x-4">
                    <form method="GET" class="relative hidden sm:block">
                        <input type="text" name="search" placeholder="Rechercher un produit..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="py-2 pl-4 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </form>

                    <a href="#" class="text-gray-600 hover:text-gray-900 relative">
                        <i class="material-symbols-rounded text-lg">notifications</i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $low_stock_count + $expired_count; ?></span>
                    </a>

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
                    <h1 class="text-2xl font-bold text-gray-900">Gestion des Stocks - FOAMIS Sarl</h1>
                    <p class="text-gray-600">Surveiller et gérer votre inventaire pharmaceutique</p>
                </div>
                <div class="flex gap-3">
                    <button id="open-stock-modal" class="px-4 py-2 pharma-primary text-white rounded-lg hover:opacity-90 flex items-center">
                        <i class="material-symbols-rounded text-base mr-2">add</i>
                        Nouveau Stock
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-green-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Stocks</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_stocks_stats; ?></h3>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="material-symbols-rounded text-green-600">inventory_2</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-blue-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Unités</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_quantity; ?></h3>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="material-symbols-rounded text-blue-600">package</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-indigo-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Valeur du Stock</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_inventory_cost, 2, ',', ' '); ?> €</h3>
                        </div>
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <i class="material-symbols-rounded text-indigo-600">euro</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-yellow-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Stock Faible</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $low_stock_count; ?></h3>
                        </div>
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="material-symbols-rounded text-yellow-600">warning</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-red-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Périmés</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $expired_count; ?></h3>
                        </div>
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="material-symbols-rounded text-red-600">error</i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire d'ajout rapide de stock -->
            <div class="bg-white shadow-md rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajout Rapide de Stock</h3>
                <form method="POST" action="../models/traitement/stock-post.php" class="flex flex-col sm:flex-row gap-4 items-end" id="quick-stock-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_stock" value="ajouter">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">
                    
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produit</label>
                        <select name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            <option value="">Sélectionner un produit</option>
                            <?php foreach ($products as $product): 
                                $stockCount = $existingStocks[$product['id']] ?? 0;
                            ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                    <?php if ($stockCount > 0): ?>
                                        (<?php echo $stockCount; ?> stock(s) existant(s))
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantité initiale</label>
                        <input type="number" name="current_quantity" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Quantité" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantité minimale</label>
                        <input type="number" name="min_quantity" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Seuil d'alerte" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Coût d'achat (€)</label>
                        <input type="number" step="0.01" name="price" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0.00" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'expiration</label>
                        <input type="date" name="expiry_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required id="quick-expiry-date">
                        <p class="text-xs text-red-500 mt-1 hidden" id="quick-expiry-warning">
                            ⚠️ La date d'expiration est dans le passé !
                        </p>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="statut" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" checked>
                        <label class="ml-2 text-sm text-gray-700">Actif</label>
                    </div>
                    
                    <div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white pharma-primary rounded-lg hover:opacity-90 flex items-center" id="quick-submit-btn">
                            <i class="material-symbols-rounded text-base mr-2">add</i>
                            Ajouter Stock
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filtres -->
            <div class="bg-white shadow-md rounded-xl p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Rechercher par nom de produit ou quantité..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produit</label>
                        <select name="product_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="0">Tous les produits</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="status_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Tous les statuts</option>
                            <option value="low" <?php echo $status_filter === 'low' ? 'selected' : ''; ?>>Stock faible</option>
                            <option value="expiring_soon" <?php echo $status_filter === 'expiring_soon' ? 'selected' : ''; ?>>Expire bientôt</option>
                            <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Périmés</option>
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

            <!-- Liste des stocks -->
            <div class="bg-white shadow-md rounded-xl overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Liste des Stocks
                        <?php if ($total_stocks > 0): ?>
                            <span class="text-sm text-gray-500 font-normal">(<?php echo $total_stocks; ?> stock(s) trouvé(s))</span>
                        <?php endif; ?>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <?php if (empty($stocks)): ?>
                        <div class="text-center py-8">
                            <i class="material-symbols-rounded text-gray-400 text-6xl mb-4">warehouse</i>
                            <p class="text-gray-500">Aucun stock trouvé</p>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité Actuelle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité Minimale</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coût d'achat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'Expiration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stocks as $stock): 
                                    $is_low_stock = $stock['current_quantity'] <= $stock['min_quantity'];
                                    $is_expired = strtotime($stock['expiry_date']) < time();
                                    $is_expiring_soon = strtotime($stock['expiry_date']) <= strtotime('+30 days') && !$is_expired;
                                    
                                    $row_class = '';
                                    $date_class = '';
                                    if ($is_expired) {
                                        $row_class = 'expired';
                                        $date_class = 'expired-date';
                                    } elseif ($is_expiring_soon) {
                                        $row_class = 'expiring-soon';
                                        $date_class = 'expiring-soon-date';
                                    } elseif ($is_low_stock) {
                                        $row_class = 'low-stock';
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 <?php echo $row_class; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if (!empty($stock['product_photo'])): ?>
                                                    <div class="w-10 h-10 rounded-lg overflow-hidden mr-3">
                                                        <img src="../uploads/<?php echo htmlspecialchars($stock['product_photo']); ?>" alt="<?php echo htmlspecialchars($stock['product_name']); ?>" class="w-full h-full object-cover">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                                        <span class="text-green-600 font-bold text-sm"><?php echo substr($stock['product_name'], 0, 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($stock['product_name']); ?></div>
                                                    <div class="text-xs text-gray-500">ID Stock: <?php echo $stock['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <?php echo $stock['current_quantity']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $stock['min_quantity']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <?php echo number_format($stock['price'], 2, ',', ' '); ?> €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $date_class; ?>">
                                            <?php echo date('d/m/Y', strtotime($stock['expiry_date'])); ?>
                                            <?php if ($is_expired): ?>
                                                <br><span class="text-xs">(Périmé)</span>
                                            <?php elseif ($is_expiring_soon): ?>
                                                <br><span class="text-xs">(Expire bientôt)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($is_expired): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                    Périmé
                                                </span>
                                            <?php elseif ($is_expiring_soon): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">
                                                    Expire bientôt
                                                </span>
                                            <?php elseif ($is_low_stock): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                                    Stock faible
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                    Normal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-green-600 hover:text-green-900 mr-3 edit-stock"
                                                data-id="<?php echo $stock['id']; ?>"
                                                data-product_id="<?php echo $stock['product_id']; ?>"
                                                data-current_quantity="<?php echo $stock['current_quantity']; ?>"
                                                data-min_quantity="<?php echo $stock['min_quantity']; ?>"
                                                data-price="<?php echo $stock['price']; ?>"
                                                data-expiry_date="<?php echo $stock['expiry_date']; ?>"
                                                data-statut="<?php echo $stock['statut']; ?>">
                                                Modifier
                                            </button>
                                            <button class="text-red-600 hover:text-red-900 delete-stock-btn" 
                                                    data-id="<?php echo $stock['id']; ?>"
                                                    data-product_name="<?php echo htmlspecialchars($stock['product_name']); ?>">
                                                Supprimer
                                            </button>
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

    <!-- Modal Ajout/Modification Stock -->
    <div id="stock-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900" id="stock-modal-title">Nouveau Stock</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>

            <div class="p-6">
                <form id="stock-form" method="POST" action="../models/traitement/stock-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_stock" id="stock-action" value="ajouter">
                    <input type="hidden" name="id" id="stock-id">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produit *</label>
                            <select name="product_id" id="stock-product" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                <option value="">Sélectionner un produit</option>
                                <?php foreach ($products as $product): 
                                    $stockCount = $existingStocks[$product['id']] ?? 0;
                                ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                        <?php if ($stockCount > 0): ?>
                                            (<?php echo $stockCount; ?> stock(s) existant(s))
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-green-600 mt-1">
                                💡 Vous pouvez ajouter plusieurs stocks pour le même produit
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" id="quantity-label">Quantité initiale *</label>
                                <input type="number" name="current_quantity" id="current-quantity" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité Minimale *</label>
                                <input type="number" name="min_quantity" id="min-quantity" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Coût d'achat (€) *</label>
                                <input type="number" step="0.01" name="price" id="stock-price" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="0.00" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'Expiration *</label>
                            <input type="date" name="expiry_date" id="expiry-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            <p class="text-xs text-red-500 mt-1 hidden" id="expiry-date-warning">
                                ⚠️ La date d'expiration est dans le passé !
                            </p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="statut" id="statut-stock" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" checked>
                            <label for="statut-stock" class="ml-2 text-sm text-gray-700">Stock actif</label>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white pharma-primary rounded-lg hover:opacity-90" id="submit-button">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content delete-modal">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Confirmation de suppression</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="material-symbols-rounded text-red-600 text-2xl">warning</i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Êtes-vous sûr de vouloir supprimer ce stock ?</h4>
                    <p class="text-gray-600 mb-4">Le stock pour "<span id="stock-to-delete-name" class="font-semibold"></span>" sera définitivement supprimé.</p>
                    <p class="text-sm text-red-600 mb-6">Cette action est irréversible.</p>
                </div>
                
                <form id="delete-stock-form" method="POST" action="../models/traitement/stock-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_stock" value="supprimer">
                    <input type="hidden" name="id" id="stock-to-delete-id">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 flex items-center">
                            <i class="material-symbols-rounded text-base mr-2">delete</i>
                            Supprimer
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

        // Gestion des modales
        const stockModal = document.getElementById('stock-modal');
        const deleteConfirmModal = document.getElementById('delete-confirm-modal');
        const openStockModalBtn = document.getElementById('open-stock-modal');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const editStockBtns = document.querySelectorAll('.edit-stock');
        const deleteStockBtns = document.querySelectorAll('.delete-stock-btn');

        // Fonction pour vérifier la date d'expiration
        function checkExpiryDate(inputId, warningId, submitBtnId = null) {
            const expiryDateInput = document.getElementById(inputId);
            const warningElement = document.getElementById(warningId);
            const submitButton = submitBtnId ? document.getElementById(submitBtnId) : null;
            
            if (expiryDateInput.value) {
                const selectedDate = new Date(expiryDateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    warningElement.classList.remove('hidden');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    warningElement.classList.add('hidden');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    return true;
                }
            }
            return true;
        }

        // Vérification de la date d'expiration dans le modal
        document.getElementById('expiry-date').addEventListener('change', function() {
            checkExpiryDate('expiry-date', 'expiry-date-warning', 'submit-button');
        });

        // Vérification de la date d'expiration dans le formulaire rapide
        document.getElementById('quick-expiry-date').addEventListener('change', function() {
            checkExpiryDate('quick-expiry-date', 'quick-expiry-warning', 'quick-submit-btn');
        });

        // Ouvrir modal stock
        openStockModalBtn.addEventListener('click', () => {
            document.getElementById('stock-modal-title').textContent = 'Nouveau Stock';
            document.getElementById('stock-action').value = 'ajouter';
            document.getElementById('stock-id').value = '';
            document.getElementById('stock-form').reset();
            document.getElementById('stock-product').disabled = false;
            document.getElementById('quantity-label').textContent = 'Quantité initiale *';
            document.getElementById('submit-button').textContent = 'Créer le Stock';
            document.getElementById('expiry-date-warning').classList.add('hidden');
            document.getElementById('submit-button').disabled = false;
            document.getElementById('submit-button').classList.remove('opacity-50', 'cursor-not-allowed');
            stockModal.classList.add('open');
        });

        // Fermer modales
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                stockModal.classList.remove('open');
                deleteConfirmModal.classList.remove('open');
            });
        });

        // Édition stock
        editStockBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('stock-modal-title').textContent = 'Modifier le Stock';
                document.getElementById('stock-action').value = 'modifier';
                document.getElementById('stock-id').value = btn.getAttribute('data-id');
                
                // Remplir le formulaire avec les données exactes du stock
                document.getElementById('stock-product').value = btn.getAttribute('data-product_id');
                document.getElementById('current-quantity').value = btn.getAttribute('data-current_quantity');
                document.getElementById('min-quantity').value = btn.getAttribute('data-min_quantity');
                document.getElementById('stock-price').value = btn.getAttribute('data-price');
                document.getElementById('expiry-date').value = btn.getAttribute('data-expiry_date');
                document.getElementById('statut-stock').checked = btn.getAttribute('data-statut') === '1';
                
                // En mode édition, on ne peut pas changer le produit
                document.getElementById('stock-product').disabled = true;
                document.getElementById('quantity-label').textContent = 'Quantité actuelle *';
                document.getElementById('submit-button').textContent = 'Modifier le Stock';
                
                // Vérifier la date d'expiration
                setTimeout(() => {
                    checkExpiryDate('expiry-date', 'expiry-date-warning', 'submit-button');
                }, 100);
                
                stockModal.classList.add('open');
            });
        });

        // Suppression stock
        deleteStockBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const stockId = btn.getAttribute('data-id');
                const productName = btn.getAttribute('data-product_name');
                
                document.getElementById('stock-to-delete-id').value = stockId;
                document.getElementById('stock-to-delete-name').textContent = productName;
                
                deleteConfirmModal.classList.add('open');
            });
        });

        // Validation du formulaire rapide
        document.getElementById('quick-stock-form').addEventListener('submit', function(e) {
            const expiryDateValid = checkExpiryDate('quick-expiry-date', 'quick-expiry-warning');
            if (!expiryDateValid) {
                e.preventDefault();
                Toastify({
                    text: "Erreur: La date d'expiration ne peut pas être dans le passé !",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: "linear-gradient(to right, #ef4444, #dc2626)",
                    },
                }).showToast();
            }
        });

        // Validation du formulaire modal
        document.getElementById('stock-form').addEventListener('submit', function(e) {
            const expiryDateValid = checkExpiryDate('expiry-date', 'expiry-date-warning');
            if (!expiryDateValid) {
                e.preventDefault();
                Toastify({
                    text: "Erreur: La date d'expiration ne peut pas être dans le passé !",
                    duration: 3000,
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
            if (e.target === stockModal) {
                stockModal.classList.remove('open');
            }
            if (e.target === deleteConfirmModal) {
                deleteConfirmModal.classList.remove('open');
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

            // Supprimer le message de la session après l'affichage
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>