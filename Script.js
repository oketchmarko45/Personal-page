function togglemenu(){
    const menu = document.querySelector(".menu-link");
    const icon = document.querySelector(".hamburger-icon");
    const desktopNav = //document.querySelector("#desktop-nav");
    menu.classList.toggle("open");
    icon.classList.toggle("open");

    // Toggle the visibility of the desktop navigation
   //desktopNav.style.display = (desktopNav.style.display === 'flex') ? 'none' : 'flex';
}
