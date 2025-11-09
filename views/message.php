<script>
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
