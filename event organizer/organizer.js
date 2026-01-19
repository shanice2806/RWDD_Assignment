document.addEventListener("DOMContentLoaded", () => {
  // Sidebar toggle button
  const toggleBtn = document.getElementById("sidebar-toggle");
  const sidebar = document.getElementById("sidebar");

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("open");
    });
  }

  // Collapsible submenu logic
  document.querySelectorAll(".menu-toggle").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.classList.toggle("active");
      const submenu = btn.nextElementSibling;
      submenu.style.display = submenu.style.display === "flex" ? "none" : "flex";
    });
  });
});
