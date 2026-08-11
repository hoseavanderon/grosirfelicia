<aside id="sidebar" class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-logo">
            POS
        </div>

        <div class="brand-text">
            Grosir Felicias
        </div>

    </div>

    <nav class="sidebar-menu">

        <a href="{{ route('home') }}" class="menu-item {{ request()->routeIs('home') ? 'active' : '' }}">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 3h1.386a.75.75 0 01.737.647l.383 2.681m0 0L6.75 15h10.5l1.994-8.972H4.756zM6.75 18.75a.75.75 0 100 1.5.75.75 0 000-1.5zm10.5 0a.75.75 0 100 1.5.75.75 0 000-1.5z" />
            </svg>

            <span class="menu-text">
                Kasir
            </span>

        </a>

        <a href="{{ route('riwayat.transaksi') }}"
            class="menu-item {{ request()->routeIs('riwayat.transaksi') ? 'active' : '' }}">


            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h6m-6 4h6m-6-8h6m-7.5-4.5h9A2.25 2.25 0 0118.75 6v12A2.25 2.25 0 0116.5 20.25h-9A2.25 2.25 0 015.25 18V6A2.25 2.25 0 017.5 3.75z" />
            </svg>

            <span class="menu-text">
                Riwayat Transaksi
            </span>

        </a>

        <a href="{{ route('langganan') }}"
            class="menu-item {{ request()->routeIs('langganan') || request()->routeIs('customers.show') ? 'active' : '' }}">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18 18.72a8.94 8.94 0 00-6-2.22 8.94 8.94 0 00-6 2.22M15 9a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>

            <span class="menu-text">
                Langganan
            </span>

        </a>

        <a href="{{ route('barang.masuk') }}"
            class="menu-item {{ request()->routeIs('barang.masuk*') ? 'active' : '' }}">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">

                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />

            </svg>

            <span class="menu-text">
                Barang Masuk
            </span>

        </a>

        <a href="{{ route('produk') }}" class="menu-item {{ request()->routeIs('produk') ? 'active' : '' }}">


            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 7.5L12 3 3 7.5m18 0v9L12 21m9-13.5L12 12m0 9v-9M3 7.5v9L12 21m0-9L3 7.5" />
            </svg>

            <span class="menu-text">
                Kelola Barang
            </span>

        </a>

        <a href="{{ route('stok') }}" class="menu-item {{ request()->routeIs('stok') ? 'active' : '' }}">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>

            <span class="menu-text">
                Cek Stok
            </span>

        </a>

        <a href="{{ route('jejak.produk') }}"
            class="menu-item {{ request()->routeIs('jejak.produk') ? 'active' : '' }}">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
            </svg>

            <span class="menu-text">
                Jejak Barang
            </span>

        </a>

        <a href="{{ route('laporan.transaksi') }}"
            class="menu-item {{ request()->routeIs('laporan.transaksi') ? 'active' : '' }}">


            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 13.125h4.5V21H3v-7.875zm6.75-6h4.5V21h-4.5V7.125zm6.75-4.125H21V21h-4.5V3z" />
            </svg>

            <span class="menu-text">
                Laporan Transaksi
            </span>

        </a>

    </nav>

    <div class="sidebar-footer">

        <a href="{{ route('filament.admin.pages.dashboard') }}" class="menu-item">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor" class="menu-svg">

                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 3.75h7.5v7.5h-7.5v-7.5zm9 0h7.5v4.5h-7.5v-4.5zm0 6h7.5v10.5h-7.5V9.75zm-9 3h7.5v7.5h-7.5v-7.5z" />

            </svg>

            <span class="menu-text">
                Admin
            </span>

        </a>

    </div>

</aside>

<div id="sidebarOverlay" class="sidebar-overlay"></div>
