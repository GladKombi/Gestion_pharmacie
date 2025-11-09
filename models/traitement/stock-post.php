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

// Gestion des actions CRUD pour les stocks
if (isset($_POST['action_stock']) && validateCsrfToken()) {
    $pdo->beginTransaction();
    try {
        if ($_POST['action_stock'] == 'ajouter') {
            $product_id = (int)$_POST['product_id'];
            $current_quantity = (int)$_POST['current_quantity'];
            $min_quantity = (int)$_POST['min_quantity'];
            $price = (float)$_POST['price'];
            $expiry_date = validateInput($_POST['expiry_date']);
            $statut = isset($_POST['statut']) ? 1 : 0;
            
            // Vérification de la date d'expiration
            $today = date('Y-m-d');
            if ($expiry_date < $today) {
                throw new Exception("La date d'expiration ne peut pas être dans le passé.");
            }
            
            // Récupérer le nom du produit pour le message
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $product_name = $product ? $product['name'] : 'Produit inconnu';
            
            // Compter le nombre de stocks existants pour ce produit
            $stmt = $pdo->prepare("SELECT COUNT(*) as stock_count FROM stock WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['stock_count'];
            
            // TOUJOURS créer un nouveau stock (plusieurs stocks par produit autorisés)
            $stmt = $pdo->prepare("INSERT INTO stock (date, product_id, current_quantity, min_quantity, price, expiry_date, statut) VALUES (NOW(), ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $current_quantity, $min_quantity, $price, $expiry_date, $statut]);
            
            $lot_number = $stock_count + 1;
            $total_cost = $current_quantity * $price;
            
            $_SESSION['message'] = [
                'text' => "Nouveau stock créé pour \"$product_name\" (Lot #$lot_number) : $current_quantity unité(s) - Coût: " . number_format($total_cost, 2, ',', ' ') . " € - Expire le " . date('d/m/Y', strtotime($expiry_date)),
                'type' => 'success'
            ];
            
        } elseif ($_POST['action_stock'] == 'modifier') {
            $id = (int)$_POST['id'];
            $current_quantity = (int)$_POST['current_quantity'];
            $min_quantity = (int)$_POST['min_quantity'];
            $price = (float)$_POST['price'];
            $expiry_date = validateInput($_POST['expiry_date']);
            $statut = isset($_POST['statut']) ? 1 : 0;
            
            // Vérification de la date d'expiration (mais on permet pour modification)
            $today = date('Y-m-d');
            if ($expiry_date < $today) {
                $expired_warning = " ⚠️ Attention: ce produit est déjà périmé!";
            } else {
                $expired_warning = "";
            }
            
            // Récupérer l'ancienne quantité et le nom du produit
            $stmt = $pdo->prepare("SELECT s.current_quantity, p.name, s.product_id, s.price as old_price FROM stock s JOIN products p ON s.product_id = p.id WHERE s.id = ?");
            $stmt->execute([$id]);
            $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($old_data) {
                $old_quantity = $old_data['current_quantity'];
                $product_name = $old_data['name'];
                $product_id = $old_data['product_id'];
                $old_price = $old_data['old_price'];
                $quantity_change = $current_quantity - $old_quantity;
                
                // Mettre à jour le stock spécifique (ne pas changer le product_id)
                $stmt = $pdo->prepare("UPDATE stock SET current_quantity = ?, min_quantity = ?, price = ?, expiry_date = ?, statut = ? WHERE id = ?");
                $stmt->execute([$current_quantity, $min_quantity, $price, $expiry_date, $statut, $id]);
                
                // Compter le nombre total de stocks pour ce produit
                $stmt = $pdo->prepare("SELECT COUNT(*) as stock_count FROM stock WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['stock_count'];
                
                $new_total_cost = $current_quantity * $price;
                $old_total_cost = $old_quantity * $old_price;
                $cost_change = $new_total_cost - $old_total_cost;
                
                if ($quantity_change > 0) {
                    $_SESSION['message'] = [
                        'text' => "Stock de \"$product_name\" modifié : +$quantity_change unité(s). Nouveau total : $current_quantity unité(s). Coût total : " . number_format($new_total_cost, 2, ',', ' ') . " €" . $expired_warning,
                        'type' => 'success'
                    ];
                } elseif ($quantity_change < 0) {
                    $_SESSION['message'] = [
                        'text' => "Stock de \"$product_name\" modifié : " . abs($quantity_change) . " unité(s) retirée(s). Nouveau total : $current_quantity unité(s). Coût total : " . number_format($new_total_cost, 2, ',', ' ') . " €" . $expired_warning,
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['message'] = [
                        'text' => "Stock de \"$product_name\" modifié. Quantité maintenue à $current_quantity unité(s). Coût total : " . number_format($new_total_cost, 2, ',', ' ') . " €" . $expired_warning,
                        'type' => 'success'
                    ];
                }
            } else {
                throw new Exception("Stock introuvable.");
            }
            
        } elseif ($_POST['action_stock'] == 'supprimer') {
            $id = (int)$_POST['id'];
            
            // Récupérer les informations du stock avant suppression
            $stmt = $pdo->prepare("SELECT p.name, s.current_quantity, s.product_id, s.expiry_date, s.price FROM stock s JOIN products p ON s.product_id = p.id WHERE s.id = ?");
            $stmt->execute([$id]);
            $stock_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stock_info) {
                $product_name = $stock_info['name'];
                $quantity = $stock_info['current_quantity'];
                $expiry_date = $stock_info['expiry_date'];
                $price = $stock_info['price'];
                $product_id = $stock_info['product_id'];
                $total_cost = $quantity * $price;
                
                // Compter les stocks restants pour ce produit
                $stmt = $pdo->prepare("SELECT COUNT(*) as remaining_stocks FROM stock WHERE product_id = ? AND id != ?");
                $stmt->execute([$product_id, $id]);
                $remaining_stocks = $stmt->fetch(PDO::FETCH_ASSOC)['remaining_stocks'];
                
                $stmt = $pdo->prepare("DELETE FROM stock WHERE id = ?");
                $stmt->execute([$id]);
                
                $remaining_text = $remaining_stocks > 0 ? " ($remaining_stocks autre(s) stock(s) restant(s) pour ce produit)" : "";
                
                $_SESSION['message'] = [
                    'text' => "Stock supprimé pour \"$product_name\" ($quantity unité(s) - Coût: " . number_format($total_cost, 2, ',', ' ') . " € - Expirait le " . date('d/m/Y', strtotime($expiry_date)) . ")$remaining_text",
                    'type' => 'success'
                ];
            } else {
                throw new Exception("Stock introuvable.");
            }
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
    $redirect_url = '../../views/stock.php';
    
    // Si on vient d'une page avec des paramètres, on les conserve
    if (isset($_POST['redirect_params']) && !empty($_POST['redirect_params'])) {
        $params = $_POST['redirect_params'];
        $redirect_url .= '?' . $params;
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// Si aucun traitement n'a été fait, redirection par défaut
header("Location: ../../views/stock.php");
exit();
?>