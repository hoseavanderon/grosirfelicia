@extends('layouts.app')

@section('title', 'Laporan Transaksi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/laporan-transaksi.css') }}">
@endpush

@section('content')

    <div x-data="laporanTransaksiPage()" class="laporan-page space-y-6">

        <div class="laporan-header">
            <h1 class="laporan-title theme-text">Laporan Transaksi</h1>
            <p class="laporan-subtitle theme-text-muted">Analitik transaksi berdasarkan rentang tanggal</p>
        </div>

        <div class="laporan-filters surface-card rounded-3xl p-4 shadow-sm sm:p-5">
            <div class="laporan-filters-grid">
                <div class="laporan-filter-group">
                    <label class="laporan-filter-label theme-text-muted">Dari Tanggal</label>
                    <div class="laporan-date-picker" @click.outside="fromCalendarOpen = false">
                        <button type="button" @click.stop="toggleFromCalendar()" class="laporan-picker-btn surface-card">
                            <span class="laporan-picker-value theme-text" x-text="formatDisplayDate(fromDate)"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="laporan-icon h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M4.5 19.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5V9m-16.5 0V6.75A2.25 2.25 0 016.75 4.5h10.5A2.25 2.25 0 0119.5 6.75V9" />
                            </svg>
                        </button>

                        <div x-show="fromCalendarOpen" x-transition x-cloak @click.stop
                            class="laporan-calendar surface-card rounded-3xl p-4 shadow-2xl">
                            <div class="laporan-calendar-nav">
                                <button type="button" @click="previousMonth('from')"
                                    class="laporan-calendar-nav-btn surface-card">Prev</button>
                                <div class="theme-text text-sm font-semibold" x-text="fromMonthName"></div>
                                <button type="button" @click="nextMonth('from')"
                                    class="laporan-calendar-nav-btn surface-card">Next</button>
                            </div>
                            <div class="theme-text-muted laporan-calendar-weekdays">
                                <template x-for="day in weekdays" :key="'from-' + day">
                                    <div x-text="day"></div>
                                </template>
                            </div>
                            <div class="theme-text laporan-calendar-days">
                                <template x-for="(day, index) in fromMonthDays" :key="'from-day-' + index">
                                    <button type="button"
                                        :class="day ? dayClass(day, 'from') : 'laporan-calendar-day laporan-calendar-day-empty'"
                                        x-text="day ? day.getDate() : ''" @click.stop="selectDay('from', day)"
                                        :disabled="!day"></button>
                                </template>
                            </div>
                            <div class="laporan-calendar-footer">
                                <button type="button" @click="resetDate('from')"
                                    class="laporan-calendar-nav-btn surface-card">Hari Ini</button>
                                <button type="button" @click="applyDate('from')"
                                    class="laporan-calendar-apply-btn">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="laporan-filter-group">
                    <label class="laporan-filter-label theme-text-muted">Sampai Tanggal</label>
                    <div class="laporan-date-picker" @click.outside="toCalendarOpen = false">
                        <button type="button" @click.stop="toggleToCalendar()" class="laporan-picker-btn surface-card">
                            <span class="laporan-picker-value theme-text" x-text="formatDisplayDate(toDate)"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="laporan-icon h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M4.5 19.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5V9m-16.5 0V6.75A2.25 2.25 0 016.75 4.5h10.5A2.25 2.25 0 0119.5 6.75V9" />
                            </svg>
                        </button>

                        <div x-show="toCalendarOpen" x-transition x-cloak @click.stop
                            class="laporan-calendar surface-card rounded-3xl p-4 shadow-2xl">
                            <div class="laporan-calendar-nav">
                                <button type="button" @click="previousMonth('to')"
                                    class="laporan-calendar-nav-btn surface-card">Sebelumnya</button>
                                <div class="theme-text text-sm font-semibold" x-text="toMonthName"></div>
                                <button type="button" @click="nextMonth('to')"
                                    class="laporan-calendar-nav-btn surface-card">Berikutnya</button>
                            </div>
                            <div class="theme-text-muted laporan-calendar-weekdays">
                                <template x-for="day in weekdays" :key="'to-' + day">
                                    <div x-text="day"></div>
                                </template>
                            </div>
                            <div class="theme-text laporan-calendar-days">
                                <template x-for="(day, index) in toMonthDays" :key="'to-day-' + index">
                                    <button type="button"
                                        :class="day ? dayClass(day, 'to') : 'laporan-calendar-day laporan-calendar-day-empty'"
                                        x-text="day ? day.getDate() : ''" @click.stop="selectDay('to', day)"
                                        :disabled="!day"></button>
                                </template>
                            </div>
                            <div class="laporan-calendar-footer">
                                <button type="button" @click="resetDate('to')"
                                    class="laporan-calendar-nav-btn surface-card">Hari Ini</button>
                                <button type="button" @click="applyDate('to')"
                                    class="laporan-calendar-apply-btn">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="loading" x-cloak class="laporan-loading surface-card rounded-3xl shadow-sm">
            <div class="laporan-loading-spinner"></div>
            <span>Memuat laporan...</span>
        </div>

        <template x-if="!loading && dataReady">
            <div class="laporan-content laporan-animate-in space-y-6">
                <div class="laporan-kpi-grid">
                    <div class="laporan-kpi-card surface-card rounded-3xl shadow-sm laporan-kpi-animate"
                        style="animation-delay: 0ms">
                        <span class="laporan-kpi-label theme-text-muted">Total Transaksi</span>
                        <span class="laporan-kpi-value theme-text"
                            x-text="formatNumber(displaySummary.total_transactions)"></span>
                    </div>
                    <div class="laporan-kpi-card surface-card rounded-3xl shadow-sm laporan-kpi-animate"
                        style="animation-delay: 60ms">
                        <span class="laporan-kpi-label theme-text-muted">Total Penjualan</span>
                        <span class="laporan-kpi-value theme-text"
                            x-text="'Rp ' + formatRupiah(displaySummary.total_sales)"></span>
                    </div>
                    <div class="laporan-kpi-card surface-card rounded-3xl shadow-sm laporan-kpi-animate"
                        style="animation-delay: 120ms">
                        <span class="laporan-kpi-label theme-text-muted">Jumlah Toko Order</span>
                        <span class="laporan-kpi-value theme-text"
                            x-text="formatNumber(displaySummary.unique_stores)"></span>
                    </div>
                    <div class="laporan-kpi-card surface-card rounded-3xl shadow-sm laporan-kpi-animate"
                        style="animation-delay: 180ms">
                        <span class="laporan-kpi-label theme-text-muted">Barang Terjual</span>
                        <span class="laporan-kpi-value theme-text"
                            x-text="formatNumber(displaySummary.total_items_sold) + ' pcs'"></span>
                    </div>
                </div>

                <div class="laporan-analytics surface-card rounded-3xl shadow-sm">
                    <div class="laporan-analytics-header">
                        <h2 class="laporan-section-title theme-text">Analytics</h2>
                        <div class="laporan-tabs">
                            <template x-for="tab in chartTabs" :key="tab.id">
                                <button type="button" class="laporan-tab"
                                    :class="activeTab === tab.id ? 'is-active' : ''" @click="switchTab(tab.id)"
                                    x-text="tab.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="laporan-chart-wrap" :class="chartTransitioning ? 'is-transitioning' : ''">
                        <div x-show="hasChartData" class="laporan-chart-canvas-wrap">
                            <canvas x-ref="analyticsChart" aria-label="Analytics chart"></canvas>
                        </div>
                        <div x-show="!hasChartData" x-cloak class="laporan-chart-empty theme-text-muted">
                            No Data Available
                        </div>
                    </div>

                    <div class="laporan-chart-stats theme-text-muted">
                        <span>Total <strong class="theme-text" x-text="activeChartStats.totalLabel"></strong></span>
                        <span class="laporan-chart-stats-sep">•</span>
                        <span>Rata-Rata <strong class="theme-text" x-text="activeChartStats.averageLabel"></strong></span>
                        <span class="laporan-chart-stats-sep">•</span>
                        <span>Nilai Tertinggi <strong class="theme-text"
                                x-text="activeChartStats.peakLabel"></strong></span>
                    </div>
                </div>

                <div class="laporan-lists-grid">
                    <div class="laporan-list-card surface-card rounded-3xl shadow-sm">
                        <div class="laporan-list-header">
                            <h2 class="laporan-section-title theme-text">Pelanggan Terbaik</h2>
                        </div>
                        <div class="laporan-list-body">
                            <div x-show="topCustomers.length === 0" x-cloak class="laporan-list-empty theme-text-muted">
                                Tidak ada pelanggan
                            </div>
                            <template x-for="(customer, index) in topCustomers" :key="customer.name + index">
                                <div class="laporan-list-item laporan-list-item-animate"
                                    :style="'animation-delay:' + (index * 50) + 'ms'">
                                    <span class="laporan-list-name theme-text" x-text="customer.name"></span>
                                    <span class="laporan-list-value"
                                        x-text="formatNumber(customer.total_pcs) + ' pcs'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="laporan-list-card surface-card rounded-3xl shadow-sm">
                        <div class="laporan-list-header">
                            <h2 class="laporan-section-title theme-text">Barang Terlaris</h2>
                        </div>
                        <div class="laporan-list-body">
                            <div x-show="bestProducts.length === 0" x-cloak class="laporan-list-empty theme-text-muted">
                                Tidak ada barang
                            </div>
                            <template x-for="(product, index) in bestProducts" :key="product.name + index">
                                <div class="laporan-list-item laporan-list-item-animate"
                                    :style="'animation-delay:' + (index * 50) + 'ms'">
                                    <span class="laporan-list-name theme-text" x-text="product.name"></span>
                                    <span class="laporan-list-value"
                                        x-text="formatNumber(product.total_pcs) + ' pcs'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/laporan-transaksi.js') }}"></script>
@endpush
