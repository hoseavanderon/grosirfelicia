@extends('layouts.app')

@section('title', $customer->nama_pelanggan)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/transaksi.css') }}">
    <link rel="stylesheet" href="{{ asset('css/langganan.css') }}">
@endpush

@section('content')

    <div x-data="langgananDetailPage({{ $customer->id }})" class="langganan-page space-y-6">

        <a href="{{ route('langganan') }}" class="langganan-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Kembali ke Daftar Pelanggan
        </a>

        <div class="langganan-header">
            <h1 class="langganan-title theme-text">{{ $customer->nama_pelanggan }}</h1>
            <p class="langganan-subtitle theme-text-muted">
                {{ $customer->no_telp ?: 'Nomor telepon belum diisi' }}
            </p>
        </div>

        <x-ui.date-range-picker />

        <div class="langganan-stats-grid">
            <div class="langganan-stat-card">
                <p class="langganan-stat-label theme-text-muted">Total Pengeluaran</p>
                <p class="langganan-stat-value theme-text">
                    Rp <span x-text="formatRupiah(stats.total_spending)"></span>
                </p>
            </div>

            <div class="langganan-stat-card">
                <p class="langganan-stat-label theme-text-muted">Total Pesanan</p>
                <p class="langganan-stat-value theme-text" x-text="stats.total_orders"></p>
            </div>

            <div class="langganan-stat-card">
                <p class="langganan-stat-label theme-text-muted">Total Item</p>
                <p class="langganan-stat-value theme-text">
                    <span x-text="formatRupiah(stats.total_items)"></span> pcs
                </p>
            </div>

            <div class="langganan-stat-card">
                <p class="langganan-stat-label theme-text-muted">Transaksi Terbesar</p>
                <p class="langganan-stat-value theme-text">
                    Rp <span x-text="formatRupiah(stats.largest_transaction)"></span>
                </p>
            </div>
        </div>

        <h2 class="langganan-section-title theme-text">Riwayat Transaksi</h2>

        <div x-show="loading" x-cloak class="langganan-loading surface-card rounded-3xl shadow-sm">
            Memuat data...
        </div>

        <div x-show="!loading && transactions.length === 0" x-cloak
            class="langganan-empty surface-card rounded-3xl shadow-sm">
            Tidak ada transaksi pada rentang tanggal ini.
        </div>

        <div x-show="!loading && transactions.length > 0" x-cloak class="langganan-grid">
            <template x-for="transaction in transactions" :key="transaction.id">
                <div @click="openDetail(transaction)" class="langganan-transaction-card">
                    <p class="langganan-transaction-date theme-text" x-text="transaction.date_label"></p>

                    <p class="langganan-transaction-label theme-text-muted">Subtotal</p>
                    <p class="langganan-transaction-value theme-text">
                        Rp <span x-text="formatRupiah(transaction.amount)"></span>
                    </p>

                    <p class="langganan-transaction-label theme-text-muted">Item</p>
                    <p class="langganan-transaction-value theme-text">
                        <span x-text="formatRupiah(transaction.total_pcs)"></span> pcs
                    </p>
                </div>
            </template>
        </div>

        <template x-teleport="body">
            <div x-show="detailModalOpen" x-cloak @keydown.escape.window="closeDetail()"
                x-transition:enter="transition ease-out duration-220" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-180"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="receipt-backdrop"
                @click.self="closeDetail()">

                <div x-show="detailModalOpen" x-transition:enter="transition ease-out duration-240"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-180"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                    class="receipt-modal relative w-full max-w-lg">

                    <button type="button" @click="closeDetail()" aria-label="Tutup"
                        class="absolute top-4 right-4 z-50 text-zinc-500 dark:text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="receipt-scroll" x-show="selectedTransaction">
                        <div class="receipt-header">
                            <div class="receipt-title">Nota Transaksi</div>
                            <div class="receipt-datetime" x-text="selectedTransaction?.datetime_label"></div>
                            <div class="receipt-trx">
                                TRX : <span x-text="selectedTransaction?.trx"></span>
                            </div>
                        </div>

                        <div class="receipt-payment-badge-wrap">
                            <span class="receipt-payment-badge payment-badge"
                                :class="selectedTransaction?.status_class"
                                x-text="selectedTransaction?.status_label"></span>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-section">
                            <div class="receipt-meta-row">
                                <span class="receipt-meta-label">Pelanggan :</span>
                                <span class="receipt-meta-value" x-text="selectedTransaction?.customer"></span>
                            </div>
                            <div class="receipt-meta-row">
                                <span class="receipt-meta-label">Nomor Telepon :</span>
                                <span class="receipt-meta-value" x-text="selectedTransaction?.phone || '-'"></span>
                            </div>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-section">
                            <template x-for="item in selectedTransaction?.items || []"
                                :key="item.detail_product_id + '-' + item.qty + '-' + item.unit_price">
                                <div class="receipt-item">
                                    <div class="receipt-item-top">
                                        <span x-text="item.product_name"></span>
                                        <span>Rp <span x-text="formatRupiah(item.line_total)"></span></span>
                                    </div>
                                    <div class="receipt-item-sub">
                                        Exp: <span x-text="item.expired_label"></span>
                                    </div>
                                    <div class="receipt-item-sub">
                                        <span x-text="item.qty"></span> × Rp
                                        <span x-text="formatRupiah(item.unit_price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-section">
                            <div class="receipt-total-row">
                                <span>Total:</span>
                                <span>Rp <span x-text="formatRupiah(selectedTransaction?.amount)"></span></span>
                            </div>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-actions-single">
                            <button type="button" @click="sendWhatsApp()" class="receipt-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                                <span>Kirim WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/receipt.js') }}"></script>
    <script src="{{ asset('js/date-range-picker.js') }}"></script>
    <script src="{{ asset('js/langganan-detail.js') }}"></script>
@endpush
