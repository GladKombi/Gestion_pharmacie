<?php

// Fonction pour construire la query string avec pagination
function buildQueryString($newParams = [])
{
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}

// Fonction de validation des entrées
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction de validation du token CSRF
function validateCsrfToken()
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['message'] = [
            'text' => 'Token de sécurité invalide',
            'type' => 'error'
        ];
        return false;
    }
    return true;
}

// Fonction de gestion des erreurs
function handleError($message, $error = null)
{
    error_log("Erreur: $message - " . ($error ? $error->getMessage() : ''));
    $_SESSION['message'] = [
        'text' => $message . ($error ? ': ' . $error->getMessage() : ''),
        'type' => 'error'
    ];
}

// Fonction de validation d'image
function validateImage($file)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Erreur lors de l'upload du fichier";
    }

    if ($file['size'] > $maxSize) {
        return "Le fichier est trop volumineux (maximum 2MB autorisé)";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return "Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF";
    }

    return true;
}

// Fonction d'upload d'image
function uploadImage($file)
{
    // Le dossier uploads doit être à la racine du projet
    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $photoName = uniqid() . '_Foamis' . time() . '.' . $extension;
    $photoPath = $uploadDir . $photoName;

    if (move_uploaded_file($file['tmp_name'], $photoPath)) {
        // Retourner seulement le nom du fichier ou le chemin relatif correct
        return $photoName;
    }

    throw new Exception("Erreur lors de l'enregistrement de l'image");
}
