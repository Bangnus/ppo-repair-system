<!-- Toast Notification Script -->
<script>
    function showToast(message, type = 'success') {
        const colors = {
            success: 'from-primary-500 to-primary-600',
            error: 'from-red-500 to-red-600',
            warning: 'from-amber-500 to-amber-600'
        };

        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl text-white font-medium bg-gradient-to-r ${colors[type]} transform transition-all duration-300 translate-x-full`;
        toast.innerHTML = `
        <div class="flex items-center gap-3">
            <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : '⚠️'}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-75">×</button>
        </div>
    `;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.remove('translate-x-full'), 100);
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    <?php if (isset($_SESSION['toast'])): ?>
        showToast('<?= $_SESSION['toast']['message'] ?>', '<?= $_SESSION['toast']['type'] ?>');
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
</script>

</body>

</html>