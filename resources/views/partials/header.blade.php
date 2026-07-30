<header class="header">

    <div class="header-left">

        <button id="sidebarToggle" class="icon-btn">
            ☰
        </button>

    </div>

    <div class="header-right">

        <button id="fullscreenBtn" class="icon-btn">

            <svg id="fullscreenIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.8" stroke="currentColor" class="header-icon">

            </svg>

        </button>

        <button id="themeToggle" class="icon-btn">

            <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="header-icon">

                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.752 15.002A9.718 9.718 0 0112 21c-5.385 0-9.75-4.365-9.75-9.75a9.718 9.718 0 015.998-9.752 7.5 7.5 0 0010.504 10.504z" />

            </svg>

        </button>

        <div class="dropdown">

            <button id="userDropdownBtn" class="user-button">

                <span>
                    Felicia Singaraja
                </span>

                <span>
                    ▼
                </span>

            </button>

            <div id="userDropdownMenu" class="dropdown-menu">

                <a href="{{ route('profile.show') }}">
                    Profile
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit">
                        Logout
                    </button>
                </form>

            </div>

        </div>

    </div>

</header>
