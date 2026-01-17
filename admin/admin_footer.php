</div> 

<footer>
  © 2026 ReLife Hub
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menuToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const body = document.body;

    if (menuToggle && adminSidebar) {
        // Toggle sidebar with button
        menuToggle.addEventListener('click', function (event) {
            event.stopPropagation(); // Prevent this click from being caught by the document listener
            adminSidebar.classList.toggle('open');
            body.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function (event) {
            const isClickInsideSidebar = adminSidebar.contains(event.target);
            const isClickOnToggle = menuToggle.contains(event.target);

            if (adminSidebar.classList.contains('open') && !isClickInsideSidebar && !isClickOnToggle) {
                adminSidebar.classList.remove('open');
                body.classList.remove('sidebar-open');
            }
        });
    }
});
</script>

</body>
</html>