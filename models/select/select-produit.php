<?php
// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Paramètres de recherche
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$category_filter = isset($_GET['category_filter']) ? (int)$_GET['category_filter'] : 0;

// Récupérer les catégories actives
$stmt = $pdo->prepare("SELECT * FROM categories WHERE statut = 1");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les produits avec pagination et recherche
$where_conditions = ["p.statut = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
}

$where_sql = implode(" AND ", $where_conditions);

// Requête pour les produits
$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category = c.id 
    WHERE $where_sql
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter le total des produits pour la pagination
$count_sql = "
    SELECT COUNT(*) 
    FROM products p 
    WHERE $where_sql
";

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_produits = $stmt->fetchColumn();
$total_pages = ceil($total_produits / $limit);

// Calculer les statistiques
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE statut = 1");
$stmt->execute();
$total_produits_stats = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM categories WHERE statut = 1");
$stmt->execute();
$total_categories = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer toutes les catégories (pour l'édition)
$stmt = $pdo->prepare("SELECT * FROM categories");
$stmt->execute();
$toutes_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);