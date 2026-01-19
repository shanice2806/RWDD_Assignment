document.addEventListener("DOMContentLoaded", () => {
  const sidebar   = document.getElementById("userSidebar");
  const toggleBtn = document.getElementById("userSidebarToggle");
  const overlay   = document.getElementById("sidebarOverlay");

  if (!sidebar || !toggleBtn || !overlay) {
    console.error("Sidebar / Toggle / Overlay not found");
    return;
  }

  function openSidebar() {
    sidebar.classList.remove("collapsed");
    document.body.classList.remove("sidebar-collapsed");
  }

  function closeSidebar() {
    sidebar.classList.add("collapsed");
    document.body.classList.add("sidebar-collapsed");
  }

  function toggleSidebar() {
    const isCollapsed = sidebar.classList.contains("collapsed");
    if (isCollapsed) openSidebar();
    else closeSidebar();
  }

  toggleBtn.addEventListener("click", toggleSidebar);
  overlay.addEventListener("click", closeSidebar);
});
