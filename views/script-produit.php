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
    const openProductModalBtn = document.getElementById('open-product-modal');
    const openCategoryModalBtn = document.getElementById('open-category-modal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const editProductBtns = document.querySelectorAll('.edit-product');
    const editCategoryBtns = document.querySelectorAll('.edit-category');
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

    // Fermer modales en cliquant en dehors
    window.addEventListener('click', (e) => {
        if (e.target === productModal) {
            productModal.classList.remove('open');
        }
        if (e.target === categoryModal) {
            categoryModal.classList.remove('open');
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