@extends('layouts.app')

@section('title', 'Jejak Produk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/jejak-produk.css') }}">
@endpush

@section('content')

    <div x-data="jejakProdukPage()" class="jejak-page space-y-6">

        <div class="jejak-header">
            <h1 class="jejak-title theme-text">Jejak Barang</h1>
            <p class="jejak-subtitle theme-text-muted">Cek pergerakan stok barang</p>
        </div>

        <div class="jejak-filters surface-card rounded-3xl p-4 shadow-sm sm:p-5">
            <div class="jejak-filters-grid">
                <div class="jejak-filter-group">
                    <label class="jejak-filter-label theme-text-muted">Barang</label>
                    <div class="jejak-product-picker" @click.outside="closeProductDropdown()">
                        <button type="button" @click="toggleProductDropdown()" class="jejak-picker-btn surface-card"
                            :disabled="productsLoading">
                            <span class="jejak-picker-value theme-text"
                                x-text="selectedProduct ? selectedProduct.name : 'Cari Barang'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="jejak-icon h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="productDropdownOpen" x-transition x-cloak
                            class="jejak-product-dropdown surface-card rounded-3xl shadow-2xl">
                            <input type="text" x-ref="productSearchInput" x-model="productQuery"
                                placeholder="Cari produk..." class="jejak-search-input">

                            <div x-show="productsLoading" class="jejak-dropdown-status">Memuat produk...</div>

                            <div class="jejak-dropdown-list">
                                <template x-for="product in filteredProducts()" :key="product.id">
                                    <button type="button" class="jejak-dropdown-item" @click="selectProduct(product)">
                                        <span x-text="product.name"></span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="!productsLoading && filteredProducts().length === 0" x-cloak
                                class="jejak-dropdown-status">
                                Produk tidak ditemukan.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="jejak-filter-group">
                    <label class="jejak-filter-label theme-text-muted">Dari</label>
                    <div class="jejak-date-picker" @click.outside="fromCalendarOpen = false">
                        <button type="button" @click.stop="toggleFromCalendar()" class="jejak-picker-btn surface-card">
                            <span class="jejak-picker-value theme-text text-sm font-medium"
                                x-text="formatDisplayDate(fromDate)"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="jejak-icon h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M4.5 19.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5V9m-16.5 0V6.75A2.25 2.25 0 016.75 4.5h10.5A2.25 2.25 0 0119.5 6.75V9" />
                            </svg>
                        </button>

                        <div x-show="fromCalendarOpen" x-transition x-cloak @click.stop
                            class="jejak-calendar surface-card rounded-3xl p-4 shadow-2xl">
                            <div class="jejak-calendar-nav">
                                <button type="button" @click="previousMonth('from')"
                                    class="jejak-calendar-nav-btn surface-card">Prev</button>
                                <div class="theme-text text-sm font-semibold" x-text="fromMonthName"></div>
                                <button type="button" @click="nextMonth('from')"
                                    class="jejak-calendar-nav-btn surface-card">Next</button>
                            </div>

                            <div class="theme-text-muted jejak-calendar-weekdays">
                                <template x-for="day in weekdays" :key="'from-' + day">
                                    <div x-text="day"></div>
                                </template>
                            </div>

                            <div class="theme-text jejak-calendar-days">
                                <template x-for="(day, index) in fromMonthDays" :key="'from-day-' + index">
                                    <button type="button"
                                        :class="day ? dayClass(day, 'from') : 'jejak-calendar-day jejak-calendar-day-empty'"
                                        x-text="day ? day.getDate() : ''" @click.stop="selectDay('from', day)"
                                        :disabled="!day">
                                    </button>
                                </template>
                            </div>

                            <div class="jejak-calendar-footer">
                                <button type="button" @click="resetDate('from')"
                                    class="jejak-calendar-nav-btn surface-card">Hari Ini</button>
                                <button type="button" @click="applyDate('from')"
                                    class="jejak-calendar-apply-btn">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="jejak-filter-group">
                    <label class="jejak-filter-label theme-text-muted">Sampai</label>
                    <div class="jejak-date-picker" @click.outside="toCalendarOpen = false">
                        <button type="button" @click.stop="toggleToCalendar()" class="jejak-picker-btn surface-card">
                            <span class="jejak-picker-value theme-text text-sm font-medium"
                                x-text="formatDisplayDate(toDate)"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="jejak-icon h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M4.5 19.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5V9m-16.5 0V6.75A2.25 2.25 0 016.75 4.5h10.5A2.25 2.25 0 0119.5 6.75V9" />
                            </svg>
                        </button>

                        <div x-show="toCalendarOpen" x-transition x-cloak @click.stop
                            class="jejak-calendar surface-card rounded-3xl p-4 shadow-2xl">
                            <div class="jejak-calendar-nav">
                                <button type="button" @click="previousMonth('to')"
                                    class="jejak-calendar-nav-btn surface-card">Prev</button>
                                <div class="theme-text text-sm font-semibold" x-text="toMonthName"></div>
                                <button type="button" @click="nextMonth('to')"
                                    class="jejak-calendar-nav-btn surface-card">Next</button>
                            </div>

                            <div class="theme-text-muted jejak-calendar-weekdays">
                                <template x-for="day in weekdays" :key="'to-' + day">
                                    <div x-text="day"></div>
                                </template>
                            </div>

                            <div class="theme-text jejak-calendar-days">
                                <template x-for="(day, index) in toMonthDays" :key="'to-day-' + index">
                                    <button type="button"
                                        :class="day ? dayClass(day, 'to') : 'jejak-calendar-day jejak-calendar-day-empty'"
                                        x-text="day ? day.getDate() : ''" @click.stop="selectDay('to', day)"
                                        :disabled="!day">
                                    </button>
                                </template>
                            </div>

                            <div class="jejak-calendar-footer">
                                <button type="button" @click="resetDate('to')"
                                    class="jejak-calendar-nav-btn surface-card">Hari Ini</button>
                                <button type="button" @click="applyDate('to')"
                                    class="jejak-calendar-apply-btn">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="loading" x-cloak class="jejak-loading surface-card rounded-3xl shadow-sm">
            <div class="jejak-loading-spinner"></div>
            <span>Memuat jejak produk...</span>
        </div>

        <template x-if="filtersReady && !loading">
            <div class="jejak-results jejak-animate-in">
                <div class="jejak-summary-grid">
                    <div class="jejak-summary-card surface-card rounded-3xl shadow-sm">
                        <span class="jejak-summary-label theme-text-muted">Stok Saat Ini</span>
                        <span class="jejak-summary-value theme-text" x-text="summary.current_stock + ' pcs'"></span>
                    </div>
                    <div class="jejak-summary-card surface-card rounded-3xl shadow-sm">
                        <span class="jejak-summary-label theme-text-muted">Total Stok Masuk</span>
                        <span class="jejak-summary-value theme-text" x-text="'+' + summary.total_in + ' pcs'"></span>
                    </div>
                    <div class="jejak-summary-card surface-card rounded-3xl shadow-sm">
                        <span class="jejak-summary-label theme-text-muted">Total Stok Keluar</span>
                        <span class="jejak-summary-value theme-text" x-text="'-' + summary.total_out + ' pcs'"></span>
                    </div>
                </div>

                <div class="jejak-timeline-section">
                    <h2 class="jejak-timeline-heading theme-text">Jejak Produk</h2>

                    <div x-show="entries.length === 0" x-cloak class="jejak-empty surface-card rounded-3xl shadow-sm">
                        <div class="jejak-empty-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5a1.125 1.125 0 00-1.125-1.125H3.375a1.125 1.125 0 00-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <p class="jejak-empty-title theme-text">Tidak ada pergerakan stok ditemukan</p>
                        <p class="jejak-empty-text theme-text-muted">selama periode yang dipilih.</p>
                    </div>

                    <div x-show="entries.length > 0" class="jejak-timeline">
                        <template x-for="(entry, index) in entries" :key="entry.id">
                            <article class="jejak-timeline-item jejak-timeline-item-animate"
                                :style="'animation-delay:' + (index * 70) + 'ms'">
                                <div class="jejak-timeline-marker">
                                    <span class="jejak-timeline-dot"></span>
                                    <span class="jejak-timeline-line" x-show="index < entries.length - 1"></span>
                                </div>

                                <div class="jejak-timeline-card surface-card rounded-3xl shadow-sm">
                                    <div class="jejak-timeline-date theme-text" x-text="entry.datetime_label"></div>

                                    <div class="jejak-timeline-body">
                                        <div class="jejak-timeline-row">
                                            <span class="jejak-timeline-key theme-text-muted">Keterangan</span>
                                            <span class="jejak-timeline-badge" x-text="entry.movement_label"></span>
                                        </div>
                                        <div class="jejak-timeline-row">
                                            <span class="jejak-timeline-key theme-text-muted">Qty</span>
                                            <span class="jejak-timeline-value theme-text" x-text="entry.qty_label"></span>
                                        </div>
                                        <div class="jejak-timeline-row">
                                            <span class="jejak-timeline-key theme-text-muted"
                                                x-text="entry.reference_label"></span>
                                            <span class="jejak-timeline-value theme-text"
                                                x-text="entry.nomor_nota"></span>
                                        </div>
                                        <div class="jejak-timeline-row">
                                            <span class="jejak-timeline-key theme-text-muted">Stok After</span>
                                            <span class="jejak-timeline-value theme-text"
                                                x-text="entry.stock_after + ' pcs'"></span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </div>
        </template>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/jejak-produk.js') }}"></script>
@endpush
