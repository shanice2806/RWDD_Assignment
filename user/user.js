document.addEventListener("DOMContentLoaded", () => {
  const userSidebar = document.getElementById("userSidebar");
  const userToggle  = document.getElementById("userSidebarToggle");

  // define the error
  if (!userSidebar || !userToggle) {
    console.error("Sidebar or toggle button not found");
    return;
  }

  userToggle.addEventListener("click", () => {
    userSidebar.classList.toggle("collapsed");
    document.body.classList.toggle("sidebar-collapsed");
  });
});
