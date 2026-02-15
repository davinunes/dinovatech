<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle Sidebar Mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>