document.addEventListener("alpine:init", () => {
    Alpine.data("appLayout", () => ({}));
});

document.addEventListener("DOMContentLoaded", () => {
    const html = document.documentElement;

    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");

    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const fullscreenIcon = document.getElementById("fullscreenIcon");

    const dropdownBtn = document.getElementById("userDropdownBtn");
    const dropdownMenu = document.getElementById("userDropdownMenu");

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    function applyTheme(theme) {
        if (theme === "dark") {
            html.classList.add("dark");

            if (themeIcon) {
                themeIcon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v1.5m0 15V21m8.25-9H21M3 12H1.5m15.364 6.364l1.06 1.06M5.576 5.576l-1.06-1.06m12.348-1.06l-1.06 1.06M5.576 18.424l-1.06 1.06M12 16.5A4.5 4.5 0 1012 7.5a4.5 4.5 0 000 9z"
                    />
                `;
            }
        } else {
            html.classList.remove("dark");

            if (themeIcon) {
                themeIcon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21.752 15.002A9.718 9.718 0 0112 21c-5.385 0-9.75-4.365-9.75-9.75a9.718 9.718 0 015.998-9.752 7.5 7.5 0 0010.504 10.504z"
                    />
                `;
            }
        }
    }

    function updateFullscreenIcon() {
        if (document.fullscreenElement) {
            fullscreenIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 9H5.25V5.25M15 9h3.75V5.25M9 15H5.25v3.75M15 15h3.75v3.75"
            />
        `;
        } else {
            fullscreenIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 9V5.25H7.5M20.25 9V5.25H16.5M3.75 15v3.75H7.5M20.25 15v3.75H16.5"
            />
        `;
        }
    }

    const savedTheme = localStorage.getItem("theme") || "light";

    applyTheme(savedTheme);
    updateFullscreenIcon();

    themeToggle?.addEventListener("click", () => {
        const isDark = html.classList.contains("dark");

        const newTheme = isDark ? "light" : "dark";

        localStorage.setItem("theme", newTheme);

        applyTheme(newTheme);
    });

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    const savedSidebar = localStorage.getItem("sidebar");

    if (savedSidebar === "collapsed" && window.innerWidth > 1024) {
        sidebar?.classList.add("collapsed");
    }

    sidebarToggle?.addEventListener("click", () => {
        if (window.innerWidth <= 1024) {
            sidebar?.classList.toggle("show");
            sidebarOverlay?.classList.toggle("show");

            return;
        }

        sidebar?.classList.toggle("collapsed");

        localStorage.setItem(
            "sidebar",
            sidebar?.classList.contains("collapsed") ? "collapsed" : "expanded",
        );
    });

    sidebarOverlay?.addEventListener("click", () => {
        sidebar?.classList.remove("show");
        sidebarOverlay?.classList.remove("show");
    });

    /*
    |--------------------------------------------------------------------------
    | Fullscreen
    |--------------------------------------------------------------------------
    */

    fullscreenBtn?.addEventListener("click", async () => {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (e) {
            console.error(e);
        }
    });

    document.addEventListener("fullscreenchange", updateFullscreenIcon);

    /*
    |--------------------------------------------------------------------------
    | User Dropdown
    |--------------------------------------------------------------------------
    */

    dropdownBtn?.addEventListener("click", (e) => {
        e.stopPropagation();

        dropdownMenu?.classList.toggle("show");
    });

    dropdownMenu?.addEventListener("click", (e) => {
        e.stopPropagation();
    });

    document.addEventListener("click", () => {
        dropdownMenu?.classList.remove("show");
    });
});
