document.addEventListener("DOMContentLoaded", () => {

  /* =========================
     SIDEBAR TOGGLE
  ========================= */
  const sidebar   = document.getElementById("userSidebar");
  const toggleBtn = document.getElementById("userSidebarToggle");
  const overlay   = document.getElementById("sidebarOverlay");

  if (sidebar && toggleBtn && overlay) {
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
  }

  /* =========================
     CONFIRM MODAL (Upload Tutorial)
     Works only if elements exist on the page
  ========================= */
  const modal   = document.getElementById("confirmModal");
  const openBtn = document.getElementById("openConfirm");
  const noBtn   = document.getElementById("noBtn");
  const yesBtn  = document.getElementById("yesBtn");
  const form    = document.getElementById("addForm");

  if (modal && openBtn && noBtn && yesBtn && form) {
    openBtn.addEventListener("click", () => {
      modal.style.display = "flex";
    });

    noBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });

    yesBtn.addEventListener("click", () => {
      form.submit();
    });

    modal.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
    });
  }

});
