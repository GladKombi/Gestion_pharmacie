<?php
// Configuration de la base de données
require_once '../connexion/connexion.php';

// Fonctions utilitaires
require_once '../includes/functions.php';
require_once '../models/select/select-produit.php';


// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>FOAMIS Sarl - Gestion des Produits</title>
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
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="#">
                        <i class="material-symbols-rounded text-base">dashboard</i>
                        <span class="ml-3">Tableau de Bord</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-semibold text-white pharma-primary rounded-lg shadow-md" href="#">
                        <i class="material-symbols-rounded text-base">inventory_2</i>
                        <span class="ml-3">Gestion des Produits</span>
                    </a>
                </li>
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="#">
                        <i class="material-symbols-rounded text-base">shopping_cart</i>
                        <span class="ml-3">Ventes & Caisse</span>
                    </a>
                </li>
            </ul>

            <h6 class="px-4 pt-3 mt-4 text-xs font-bold text-gray-500 uppercase">Configuration</h6>

            <ul class="space-y-1 mt-2">
                <li class="p-1">
                    <a class="flex items-center p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg" href="#">
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
                        <li><a href="#" class="opacity-80">Gestion</a></li>
                        <li class="px-2">/</li>
                        <li class="font-bold text-gray-800">Produits & Catégories</li>
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
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">5</span>
                    </a>

                    <button id="toggle-sidebar" class="text-gray-600 hover:text-gray-900 lg:hidden">
                        <i class="material-symbols-rounded text-lg">menu</i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Contenu principal -->
        <div class="dashboard-container">
            <!-- En-tête avec boutons d'action -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestion des Produits - FOAMIS Sarl</h1>
                    <p class="text-gray-600">Ajouter, modifier et gérer vos produits pharmaceutiques</p>
                </div>
                <div class="flex gap-3">
                    <button id="open-category-modal" class="px-4 py-2 bg-white text-green-700 border border-green-700 rounded-lg hover:bg-green-50 flex items-center">
                        <i class="material-symbols-rounded text-base mr-2">category</i>
                        Catégories
                    </button>
                    <button id="open-product-modal" class="px-4 py-2 pharma-primary text-white rounded-lg hover:opacity-90 flex items-center">
                        <i class="material-symbols-rounded text-base mr-2">add</i>
                        Nouveau Produit
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-green-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Produits</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_produits_stats; ?></h3>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="material-symbols-rounded text-green-600">inventory_2</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-blue-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Catégories</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_categories; ?></h3>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="material-symbols-rounded text-blue-600">category</i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-yellow-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Produits Actifs</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_produits_stats; ?></h3>
                        </div>
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="material-symbols-rounded text-yellow-600">check_circle</i>
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
                            placeholder="Rechercher par nom ou description..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                        <select name="category_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id']; ?>" <?php echo $category_filter == $categorie['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categorie['name']); ?>
                                </option>
                            <?php endforeach; ?>
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

            <!-- Liste des produits -->
            <div class="bg-white shadow-md rounded-xl overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Liste des Produits
                        <?php if ($total_produits > 0): ?>
                            <span class="text-sm text-gray-500 font-normal">(<?php echo $total_produits; ?> produit(s) trouvé(s))</span>
                        <?php endif; ?>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <?php if (empty($produits)): ?>
                        <div class="text-center py-8">
                            <i class="material-symbols-rounded text-gray-400 text-6xl mb-4">inventory_2</i>
                            <p class="text-gray-500">Aucun produit trouvé</p>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($produits as $produit): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if (!empty($produit['photo'])): ?>
                                                    <div class="w-10 h-10 rounded-lg overflow-hidden mr-3">
                                                        <img src="../uploads/<?php echo htmlspecialchars($produit['photo']); ?>" alt="<?php echo htmlspecialchars($produit['name']); ?>" class="w-full h-full object-cover">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                                        <span class="text-green-600 font-bold text-sm"><?php echo substr($produit['name'], 0, 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($produit['name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($produit['category_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($produit['description']); ?></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $produit['statut'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded-full">
                                                <?php echo $produit['statut'] ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-white bg-green-600 px-4 py-2 rounded-lg hover:text-gray-90 mr-3 edit-product"
                                                data-id="<?php echo $produit['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($produit['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($produit['description']); ?>"
                                                data-category="<?php echo $produit['category']; ?>"
                                                data-photo="<?php echo htmlspecialchars($produit['photo']); ?>"
                                                data-statut="<?php echo $produit['statut']; ?>">
                                                Modifier
                                            </button>
                                            <button class="text-white px-4 py-2 hover:text-gray-900 delete-product-btn bg-red-600 rounded-lg" 
                                                    data-id="<?php echo $produit['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($produit['name']); ?>">
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

    <!-- Modal Ajout/Modification Produit -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900" id="product-modal-title">Nouveau Produit</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>

            <div class="p-6">
                <form id="product-form" method="POST" action="../models/traitement/produit-post.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_produit" id="product-action" value="ajouter">
                    <input type="hidden" name="id" id="product-id">
                    <input type="hidden" name="photo_actuelle" id="photo-actuelle">
                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom du Produit *</label>
                            <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ex: Doliprane 1000mg" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <input type="text" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Description du produit" maxlength="50">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                            <input type="file" name="photo" id="photo-input" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Formats acceptés: JPG, PNG, GIF (max 2MB)</p>
                            <div id="photo-preview" class="mt-2"></div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="statut" id="statut-produit" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" checked>
                            <label for="statut-produit" class="ml-2 text-sm text-gray-700">Produit actif</label>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white pharma-primary rounded-lg hover:opacity-90">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Gestion des Catégories -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Gestion des Catégories</h3>
                <button class="text-gray-400 hover:text-gray-600 close-modal">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </div>

            <div class="p-6">
                <!-- Formulaire nouvelle catégorie -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-md font-medium text-gray-900 mb-3" id="category-form-title">Nouvelle Catégorie</h4>
                    <form method="POST" action="../models/traitement/categorie-post.php" id="category-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action_categorie" id="category-action" value="ajouter">
                        <input type="hidden" name="id" id="category-id">
                        <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la catégorie *</label>
                                <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nom de la catégorie" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" rows="3" placeholder="Description de la catégorie"></textarea>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="statut" id="statut-categorie" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" checked>
                                <label for="statut-categorie" class="ml-2 text-sm text-gray-700">Catégorie active</label>
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white pharma-primary rounded-lg hover:opacity-90">Enregistrer</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Liste des catégories -->
                <div class="space-y-3">
                    <?php foreach ($toutes_categories as $categorie): ?>
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="material-symbols-rounded text-green-600 text-sm">local_pharmacy</i>
                                </div>
                                <div>
                                    <span class="text-sm font-medium"><?php echo htmlspecialchars($categorie['name']); ?></span>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($categorie['description']); ?></p>
                                    <span class="text-xs <?php echo $categorie['statut'] ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $categorie['statut'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-green-600 hover:text-green-900 text-sm edit-category"
                                    data-id="<?php echo $categorie['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($categorie['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($categorie['description']); ?>"
                                    data-statut="<?php echo $categorie['statut']; ?>">
                                    Modifier
                                </button>
                                <form method="POST" action="../models/traitement/categorie-post.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action_categorie" value="supprimer">
                                    <input type="hidden" name="id" value="<?php echo $categorie['id']; ?>">
                                    <input type="hidden" name="redirect_params" value="<?php echo http_build_query($_GET); ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?')">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-end">
                <button class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 close-modal">Fermer</button>
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Êtes-vous sûr de vouloir supprimer ce produit ?</h4>
                    <p class="text-gray-600 mb-4">Le produit "<span id="product-to-delete-name" class="font-semibold"></span>" sera définitivement supprimé.</p>
                    <p class="text-sm text-red-600 mb-6">Cette action est irréversible.</p>
                </div>
                
                <form id="delete-product-form" method="POST" action="../models/traitement/produit-post.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action_produit" value="supprimer">
                    <input type="hidden" name="id" id="product-to-delete-id">
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
        const productModal = document.getElementById('product-modal');
        const categoryModal = document.getElementById('category-modal');
        const deleteConfirmModal = document.getElementById('delete-confirm-modal');
        const openProductModalBtn = document.getElementById('open-product-modal');
        const openCategoryModalBtn = document.getElementById('open-category-modal');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const editProductBtns = document.querySelectorAll('.edit-product');
        const editCategoryBtns = document.querySelectorAll('.edit-category');
        const deleteProductBtns = document.querySelectorAll('.delete-product-btn');
        const photoInput = document.getElementById('photo-input');
        const photoPreview = document.getElementById('photo-preview');

        // Gestion de l'aperçu de la photo
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validation côté client
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (file.size > maxSize) {
                    alert('Le fichier est trop volumineux (max 2MB)');
                    this.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `<img src="${e.target.result}" class="photo-preview" alt="Aperçu photo">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Ouvrir modal produit
        openProductModalBtn.addEventListener('click', () => {
            document.getElementById('product-modal-title').textContent = 'Nouveau Produit';
            document.getElementById('product-action').value = 'ajouter';
            document.getElementById('product-id').value = '';
            document.getElementById('photo-actuelle').value = '';
            document.getElementById('product-form').reset();
            photoPreview.innerHTML = '';
            productModal.classList.add('open');
        });

        // Ouvrir modal catégorie
        openCategoryModalBtn.addEventListener('click', () => {
            document.getElementById('category-form-title').textContent = 'Nouvelle Catégorie';
            document.getElementById('category-action').value = 'ajouter';
            document.getElementById('category-id').value = '';
            document.getElementById('category-form').reset();
            categoryModal.classList.add('open');
        });

        // Fermer modales
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                productModal.classList.remove('open');
                categoryModal.classList.remove('open');
                deleteConfirmModal.classList.remove('open');
            });
        });

        // Édition produit
        editProductBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('product-modal-title').textContent = 'Modifier le Produit';
                document.getElementById('product-action').value = 'modifier';
                document.getElementById('product-id').value = btn.getAttribute('data-id');
                document.getElementById('photo-actuelle').value = btn.getAttribute('data-photo');
                
                // Remplir le formulaire avec les données
                document.querySelector('input[name="name"]').value = btn.getAttribute('data-name');
                document.querySelector('input[name="description"]').value = btn.getAttribute('data-description');
                document.querySelector('select[name="category"]').value = btn.getAttribute('data-category');
                document.querySelector('input[name="statut"]').checked = btn.getAttribute('data-statut') === '1';
                
                // Afficher l'aperçu de la photo actuelle
                const currentPhoto = btn.getAttribute('data-photo');
                if (currentPhoto) {
                    photoPreview.innerHTML = `<img src="${currentPhoto}" class="photo-preview" alt="Photo actuelle">`;
                } else {
                    photoPreview.innerHTML = '';
                }
                
                productModal.classList.add('open');
            });
        });

        // Édition catégorie
        editCategoryBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('category-form-title').textContent = 'Modifier la Catégorie';
                document.getElementById('category-action').value = 'modifier';
                document.getElementById('category-id').value = btn.getAttribute('data-id');
                
                // Remplir le formulaire avec les données
                document.querySelector('input[name="name"]').value = btn.getAttribute('data-name');
                document.querySelector('textarea[name="description"]').value = btn.getAttribute('data-description');
                document.querySelector('input[name="statut"]').checked = btn.getAttribute('data-statut') === '1';
                
                categoryModal.classList.add('open');
            });
        });

        // Suppression produit
        deleteProductBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-id');
                const productName = btn.getAttribute('data-name');
                
                document.getElementById('product-to-delete-id').value = productId;
                document.getElementById('product-to-delete-name').textContent = productName;
                
                deleteConfirmModal.classList.add('open');
            });
        });

        // Fermer modales en cliquant en dehors
        window.addEventListener('click', (e) => {
            if (e.target === productModal) {
                productModal.classList.remove('open');
            }
            if (e.target === categoryModal) {
                categoryModal.classList.remove('open');
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
                gravity: "top", // `top` ou `bottom`
                position: "right", // `left`, `center` ou `right`
                stopOnFocus: true, // Arrête la minuterie si l'utilisateur interagit avec la fenêtre
                style: {
                    background: "linear-gradient(to right, <?= ($_SESSION['message']['type'] == 'success') ? '#22c55e, #16a34a' : '#ef4444, #dc2626' ?>)",
                },
                onClick: function() {} // Callback après le clic
            }).showToast();

            // Supprimer le message de la session après l'affichage
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>