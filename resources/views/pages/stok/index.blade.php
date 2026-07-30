@extends('layouts.app')

@section('title', 'Cek Stok Harian')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/stok.css') }}">
@endpush

@section('content')

    <div x-data="stokAuditPage()" class="stok-page space-y-6">

        <div class="stok-sticky-toolbar surface-card rounded-3xl p-4 shadow-sm sm:p-5">
            <div class="stok-toolbar-layout">
                <div class="stok-header-intro">
                    <h1 class="stok-title theme-text">Cek Stok</h1>
                    <p class="stok-subtitle theme-text-muted">Cek stok harian per provider</p>
                </div>

                <div class="stok-date-picker" @click.outside="calendarOpen = false">
                    <button type="button" @click="calendarOpen = !calendarOpen" class="stok-date-btn surface-card">
                        <span class="theme-text text-sm font-medium" x-text="formattedDate"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stok-icon h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M4.5 19.5A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5V9m-16.5 0V6.75A2.25 2.25 0 016.75 4.5h10.5A2.25 2.25 0 0119.5 6.75V9" />
                        </svg>
                    </button>

                    <div x-show="calendarOpen" x-transition x-cloak
                        class="stok-calendar surface-card rounded-3xl p-4 shadow-2xl">
                        <div class="flex items-center justify-between gap-3">
                            <button type="button" @click="previousMonth()"
                                class="surface-card rounded-2xl px-3 py-2 text-xs font-medium">Prev</button>
                            <div class="theme-text text-sm font-semibold" x-text="monthName"></div>
                            <button type="button" @click="nextMonth()"
                                class="surface-card rounded-2xl px-3 py-2 text-xs font-medium">Next</button>
                        </div>

                        <div
                            class="theme-text-muted mt-4 grid grid-cols-7 gap-2 text-center text-[10px] font-semibold uppercase tracking-[0.2em]">
                            <template x-for="day in weekdays" :key="day">
                                <div x-text="day"></div>
                            </template>
                        </div>

                        <div class="theme-text mt-3 grid grid-cols-7 gap-2 text-center text-sm">
                            <template x-for="(day, index) in monthDays" :key="index">
                                <button type="button"
                                    :class="day ? dayClass(day) : 'stok-calendar-day stok-calendar-day-empty'"
                                    x-text="day ? day.getDate() : ''" @click="selectDay(day)" :disabled="!day">
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <button type="button" @click="resetDate()"
                                class="surface-card rounded-2xl px-4 py-2 text-xs font-semibold">Hari Ini</button>
                            <button type="button" @click="applyDate()"
                                class="rounded-2xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">Terapkan</button>
                        </div>
                    </div>
                </div>

                <div class="stok-actions">
                    <button type="button" @click="checkAll()" class="stok-action-btn"
                        :class="checkedAll ? 'is-success' : ''" :disabled="loading || saving">
                        <span class="stok-action-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stok-icon h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span class="stok-action-label" x-text="checkedAll ? 'Selesai' : 'Check Semua'"></span>
                    </button>
                    <button type="button" @click="saveProgress()" class="stok-action-btn stok-action-btn-primary"
                        :disabled="loading || saving">
                        <span class="stok-action-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stok-icon h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                            </svg>
                        </span>
                        <span class="stok-action-label" x-text="saving ? 'Menyimpan...' : 'Save'"></span>
                    </button>
                    <button type="button" @click="copyWhatsAppReport()" class="stok-action-btn"
                        :class="copied ? 'is-success' : ''" :disabled="loading">
                        <span class="stok-action-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stok-icon h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 7.5h7.5m-7.5 3h7.5m-7.5 3h7.5M5.25 5.25h13.5A2.25 2.25 0 0121 7.5v10.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 19.5V7.5A2.25 2.25 0 015.25 5.25z" />
                            </svg>
                        </span>
                        <span class="stok-action-label" x-text="copied ? 'Tersalin' : 'Copy Text'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div x-show="loading" x-cloak class="stok-state surface-card rounded-3xl shadow-sm">
            Memuat data stok...
        </div>

        <div x-show="!loading && providers.length === 0" x-cloak class="stok-state surface-card rounded-3xl shadow-sm">
            Tidak ada produk untuk diaudit.
        </div>

        <div class="stok-accordions space-y-3" x-show="!loading && providers.length > 0">
            <template x-for="provider in providers" :key="provider.provider_id">
                <div class="stok-accordion surface-card rounded-3xl shadow-sm">
                    <button type="button" class="stok-accordion-header" @click="toggleProvider(provider.provider_id)"
                        :aria-expanded="isProviderOpen(provider.provider_id)">
                        <div class="stok-accordion-title-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stok-accordion-chevron stok-icon h-4 w-4"
                                :class="isProviderOpen(provider.provider_id) ? 'is-open' : ''" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>

                            <div class="stok-accordion-copy">
                                <span class="stok-accordion-title theme-text" x-text="provider.provider_name"></span>
                                <span class="stok-accordion-meta">
                                    <span x-text="provider.products.length + ' Products'"></span>
                                    <span class="stok-accordion-meta-sep">•</span>
                                    <span x-text="providerNeedCheckingCount(provider) + ' Perlu Cek'"></span>
                                </span>
                            </div>
                        </div>
                    </button>

                    <div class="stok-accordion-body" :class="isProviderOpen(provider.provider_id) ? 'is-open' : ''">
                        <div class="stok-accordion-body-inner">
                            <template x-for="product in provider.products" :key="product.product_id">
                                <div class="stok-product-row">
                                    <div class="stok-product-name theme-text" x-text="product.name"></div>

                                    <div class="stok-product-pcs">
                                        <input type="number" min="0" inputmode="numeric" class="stok-pcs-input"
                                            x-model.number="product.pcs" @input="onPcsInput(product)">
                                        <span class="stok-pcs-label">PCS</span>
                                    </div>

                                    <button type="button" class="audit-check-btn" @click="toggleProductCheck(product)"
                                        :aria-label="'Status audit ' + product.name">
                                        <span class="audit-check" :class="checkClass(product.check_state)">
                                            <svg x-show="product.check_state === 'system'"
                                                xmlns="http://www.w3.org/2000/svg" class="audit-check-icon"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/stok-audit.js') }}"></script>
@endpush
