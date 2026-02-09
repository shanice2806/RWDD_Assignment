<footer>
  © 2026 ReLife Hub
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menuToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const body = document.body;

    if (menuToggle && adminSidebar) {
        menuToggle.addEventListener('click', function (event) {
            event.stopPropagation(); // prevent open and instant close
            adminSidebar.classList.toggle('open');
            body.classList.toggle('sidebar-open');
        });

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