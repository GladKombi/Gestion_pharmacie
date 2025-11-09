<?php
include '../../connexion/connexion.php';
require_once '../../includes/functions.php';
// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestion des actions CRUD pour les catégories

if (isset($_POST['action_categorie']) && validateCsrfToken()) {
    $pdo->beginTransaction();
    try {
        if ($_POST['action_categorie'] == 'ajouter') {
            $name = validateInput($_POST['name']);
            $description = validateInput($_POST['description']);
            $statut = isset($_POST['statut']) ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO categories (name, description, statut) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $statut]);

            $_SESSION['message'] = [
                'text' => 'Catégorie ajoutée avec succès',
                'type' => 'success'
            ];
        } elseif ($_POST['action_categorie'] == 'modifier') {
            $id = (int)$_POST['id'];
            $name = validateInput($_POST['name']);
            $description = validateInput($_POST['description']);
            $statut = isset($_POST['statut']) ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, statut = ? WHERE id = ?");
            $stmt->execute([$name, $description, $statut, $id]);

            $_SESSION['message'] = [
                'text' => 'Catégorie modifiée avec succès',
                'type' => 'success'
            ];
        } elseif ($_POST['action_categorie'] == 'supprimer') {
            $id = (int)$_POST['id'];

            // Vérifier si la catégorie est utilisée par des produits
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                throw new Exception("Impossible de supprimer cette catégorie car elle est utilisée par des produits");
            }

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['message'] = [
                'text' => 'Catégorie supprimée avec succès',
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
    $redirect_url = '../../views/produits.php';

    // Si on vient d'une page avec des paramètres, on les conserve
    if (isset($_POST['redirect_params']) && !empty($_POST['redirect_params'])) {
        $params = $_POST['redirect_params'];
        $redirect_url .= '?' . $params;
    }

    header("Location: " . $redirect_url);
    exit();
}

// Gestion des actions CRUD pour les produits
if (isset($_POST['action_produit']) && validateCsrfToken()) {
    $pdo->beginTransaction();
    try {
        if ($_POST['action_produit'] == 'ajouter') {
            $name = validateInput($_POST['name']);
            $description = validateInput($_POST['description']);
            $category = (int)$_POST['category'];
            $statut = isset($_POST['statut']) ? 1 : 0;
            
            // Gestion de l'upload de photo
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $validation = validateImage($_FILES['photo']);
                if ($validation !== true) {
                    throw new Exception($validation);
                }
                $photo = uploadImage($_FILES['photo']);
            }
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, category, photo, statut) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $category, $photo, $statut]);
            
            $_SESSION['message'] = [
                'text' => 'Produit ajouté avec succès',
                'type' => 'success'
            ];
            
        } elseif ($_POST['action_produit'] == 'modifier') {
            $id = (int)$_POST['id'];
            $name = validateInput($_POST['name']);
            $description = validateInput($_POST['description']);
            $category = (int)$_POST['category'];
            $statut = isset($_POST['statut']) ? 1 : 0;
            
            // Gestion de l'upload de photo
            $photo = $_POST['photo_actuelle'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $validation = validateImage($_FILES['photo']);
                if ($validation !== true) {
                    throw new Exception($validation);
                }
                // Supprimer l'ancienne photo si elle existe
                if (!empty($photo) && file_exists($photo)) {
                    unlink($photo);
                }
                $photo = uploadImage($_FILES['photo']);
            }
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, category = ?, photo = ?, statut = ? WHERE id = ?");
            $stmt->execute([$name, $description, $category, $photo, $statut, $id]);
            
            $_SESSION['message'] = [
                'text' => 'Produit modifié avec succès',
                'type' => 'success'
            ];
            
        } elseif ($_POST['action_produit'] == 'supprimer') {
            $id = (int)$_POST['id'];
            
            // Récupérer la photo avant suppression pour la supprimer du serveur
            $stmt = $pdo->prepare("SELECT photo FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $produit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($produit && !empty($produit['photo']) && file_exists($produit['photo'])) {
                unlink($produit['photo']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['message'] = [
                'text' => 'Produit supprimé avec succès',
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
    $redirect_url = '../../views/produits.php';
    
    // Si on vient d'une page avec des paramètres, on les conserve
    if (isset($_POST['redirect_params']) && !empty($_POST['redirect_params'])) {
        $params = $_POST['redirect_params'];
        $redirect_url .= '?' . $params;
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// Si aucun traitement n'a été fait, redirection par défaut
header("Location: ../../views/produits.php");
exit();

