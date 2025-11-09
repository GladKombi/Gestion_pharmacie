<?php
include '../../connexion/connexion.php';
require_once '../../includes/functions.php';

// Vérifier si la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestion des actions CRUD pour les ventes
if (isset($_POST['action_sale']) && validateCsrfToken()) {
    $pdo->beginTransaction();
    try {
        if ($_POST['action_sale'] == 'ajouter') {
            $sale_date = validateInput($_POST['sale_date']);
            $customer_name = validateInput($_POST['customer_name']);
            $cart_items = $_POST['cart_items'];
            
            // Vérifier qu'il y a des articles dans le panier
            if (empty($cart_items)) {
                throw new Exception("Le panier est vide.");
            }
            
            // Vérifier les prix avant de créer la vente
            foreach ($cart_items as $item) {
                $product_id = (int)$item['product_id'];
                $stock_id = (int)$item['stock_id'];
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$item['unit_price'];
                
                // Vérifier la disponibilité du stock
                $stmt = $pdo->prepare("SELECT current_quantity, price as cost_price FROM stock WHERE id = ?");
                $stmt->execute([$stock_id]);
                $stock = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$stock) {
                    throw new Exception("Stock introuvable.");
                }
                
                if ($stock['current_quantity'] < $quantity) {
                    throw new Exception("Stock insuffisant pour l'article sélectionné.");
                }
                
                // Vérifier que le prix de vente n'est pas inférieur au coût d'achat
                if ($unit_price < $stock['cost_price']) {
                    // Récupérer le nom du produit pour le message d'erreur
                    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    $product_name = $product ? $product['name'] : 'Produit inconnu';
                    
                    throw new Exception("Le prix de vente pour \"$product_name\" (".number_format($unit_price, 2, ',', ' ')." €) est inférieur au coût d'achat (".number_format($stock['cost_price'], 2, ',', ' ')." €).");
                }
            }
            
            // Créer la vente
            $stmt = $pdo->prepare("INSERT INTO sales (sale_date, customer_name, statut) VALUES (?, ?, 1)");
            $stmt->execute([$sale_date, $customer_name]);
            $sale_id = $pdo->lastInsertId();
            
            $total_amount = 0;
            $items_count = 0;
            $total_profit = 0;
            
            // Ajouter les articles de la vente
            foreach ($cart_items as $item) {
                $product_id = (int)$item['product_id'];
                $stock_id = (int)$item['stock_id'];
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$item['unit_price'];
                
                // Récupérer le coût d'achat pour calculer la marge
                $stmt = $pdo->prepare("SELECT price as cost_price FROM stock WHERE id = ?");
                $stmt->execute([$stock_id]);
                $stock = $stmt->fetch(PDO::FETCH_ASSOC);
                $cost_price = $stock['cost_price'];
                
                // Ajouter l'article à la vente
                $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, stock_id, quantity, unit_price, statut) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$sale_id, $product_id, $stock_id, $quantity, $unit_price]);
                
                // Décrémenter le stock
                $stmt = $pdo->prepare("UPDATE stock SET current_quantity = current_quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $stock_id]);
                
                $item_total = $quantity * $unit_price;
                $item_cost = $quantity * $cost_price;
                $item_profit = $item_total - $item_cost;
                
                $total_amount += $item_total;
                $total_profit += $item_profit;
                $items_count++;
            }
            
            $profit_margin = $total_amount > 0 ? ($total_profit / $total_amount * 100) : 0;
            
            $_SESSION['message'] = [
                'text' => "Vente #" . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . " créée avec succès. $items_count article(s) - Total: " . number_format($total_amount, 2, ',', ' ') . " € - Marge: " . number_format($total_profit, 2, ',', ' ') . " € (" . number_format($profit_margin, 1, ',', ' ') . "%)",
                'type' => 'success'
            ];
            
        } elseif ($_POST['action_sale'] == 'annuler') {
            $id = (int)$_POST['id'];
            
            // Récupérer les informations de la vente
            $stmt = $pdo->prepare("SELECT customer_name FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            $sale_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale_info) {
                throw new Exception("Vente introuvable.");
            }
            
            // Récupérer les articles de la vente
            $stmt = $pdo->prepare("SELECT stock_id, quantity FROM sale_items WHERE sale_id = ?");
            $stmt->execute([$id]);
            $sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Réapprovisionner les stocks
            foreach ($sale_items as $item) {
                $stmt = $pdo->prepare("UPDATE stock SET current_quantity = current_quantity + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['stock_id']]);
            }
            
            // Marquer la vente comme annulée
            $stmt = $pdo->prepare("UPDATE sales SET statut = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            // Marquer les articles comme annulés
            $stmt = $pdo->prepare("UPDATE sale_items SET statut = 0 WHERE sale_id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['message'] = [
                'text' => "Vente #" . str_pad($id, 6, '0', STR_PAD_LEFT) . " annulée avec succès. Les stocks ont été réapprovisionnés.",
                'type' => 'success'
            ];
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = [
            'text' => 'Erreur: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
    
    // Redirection avec préservation des paramètres
    $redirect_url = '../../views/ventes.php';
    
    if (isset($_POST['redirect_params']) && !empty($_POST['redirect_params'])) {
        $params = $_POST['redirect_params'];
        $redirect_url .= '?' . $params;
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// Si aucun traitement n'a été fait, redirection par défaut
header("Location: ../../views/ventes.php");
exit();
?>