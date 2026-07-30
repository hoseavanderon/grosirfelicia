@extends('layouts.app')

@section('title', 'Kelola Produk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/produk.css') }}">
@endpush

@section('content')

    <div x-data="produkDashboard()" class="produk-page space-y-6">

        <div class="produk-header">
            <h1 class="produk-title theme-text">Kelola Produk</h1>
        </div>

        <div class="produk-grid">

            {{-- Card 1: Best Sellers --}}
            <div class="produk-card">
                <div class="produk-card-header">
                    <h2 class="produk-card-title theme-text">Barang Terlaris Tahun Ini</h2>
                </div>

                <div class="produk-card-body" role="region" aria-label="Daftar barang terlaris tahun ini">
                    <div x-show="bestSellersLoading" x-cloak class="produk-loading">
                        Memuat data...
                    </div>

                    <div x-show="!bestSellersLoading && bestSellers.length === 0" x-cloak class="produk-empty">
                        Belum ada penjualan tahun ini.
                    </div>

                    <template x-for="item in bestSellers" :key="item.product_id">
                        <div class="produk-list-item">
                            <div class="produk-item-main">
                                <p class="produk-item-name theme-text" x-text="item.name"></p>
                            </div>
                            <span class="produk-badge produk-badge-success" x-text="item.total_sold"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Card 2: Expiring Soon --}}
            <div class="produk-card">
                <div class="produk-card-header">
                    <h2 class="produk-card-title theme-text">Barang Expired Dalam 1,5 Bulan</h2>
                    <button type="button" @click="copyExpiringReport()"
                        :disabled="copyingExpiring || expiringLoading || expiring.length === 0"
                        class="produk-card-action">
                        <span x-show="!copyingExpiring">Kirim WA</span>
                        <span x-show="copyingExpiring">Menyalin...</span>
                    </button>
                </div>

                <div class="produk-card-body" role="region" aria-label="Daftar barang mendekati expired">
                    <div x-show="expiringLoading" x-cloak class="produk-loading">
                        Memuat data...
                    </div>

                    <div x-show="!expiringLoading && expiring.length === 0" x-cloak class="produk-empty">
                        Tidak ada barang mendekati expired.
                    </div>

                    <template x-for="item in expiring" :key="item.id">
                        <div class="produk-list-item">
                            <div class="produk-item-main">
                                <p class="produk-item-name theme-text" x-text="item.name"></p>
                                <p class="produk-item-sub">
                                    <span x-text="item.stock"></span> PCS
                                </p>
                            </div>
                            <span class="produk-badge produk-badge-warning" x-text="item.expired_label"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Card 3: Critical Stock --}}
            <div class="produk-card">
                <div class="produk-card-header">
                    <h2 class="produk-card-title theme-text">Stok Kritis (&lt;30 PCS)</h2>
                    <button type="button" @click="copyCriticalReport()"
                        :disabled="copyingCritical || criticalLoading || critical.length === 0"
                        class="produk-card-action">
                        <span x-show="!copyingCritical">Kirim WA</span>
                        <span x-show="copyingCritical">Menyalin...</span>
                    </button>
                </div>

                <div class="produk-card-body" role="region" aria-label="Daftar stok kritis">
                    <div x-show="criticalLoading" x-cloak class="produk-loading">
                        Memuat data...
                    </div>

                    <div x-show="!criticalLoading && critical.length === 0" x-cloak class="produk-empty">
                        Tidak ada stok kritis.
                    </div>

                    <template x-for="item in critical" :key="item.id">
                        <div class="produk-list-item">
                            <div class="produk-item-main">
                                <p class="produk-item-name theme-text" x-text="item.name"></p>
                            </div>
                            <span class="produk-badge produk-badge-danger">
                                <span x-text="item.stock"></span> PCS
                            </span>
                        </div>
                    </template>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/produk-dashboard.js') }}"></script>
@endpush
