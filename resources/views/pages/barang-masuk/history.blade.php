@extends('layouts.app')

@section('title', 'Riwayat Barang Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/barang-masuk.css') }}">
@endpush

@section('content')

    <div x-data="incomingGoodsHistory()" class="barang-masuk-page space-y-6">

        <a href="{{ route('barang.masuk') }}" class="barang-masuk-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Riwayat Barang Masuk
        </a>

        <div class="surface-card rounded-3xl p-4 shadow-sm sm:p-5">
            <div x-show="loading" x-cloak class="barang-masuk-loading">
                Memuat riwayat...
            </div>

            <div x-show="!loading && records.length === 0" x-cloak class="barang-masuk-loading">
                Belum ada riwayat barang masuk.
            </div>

            <div x-show="!loading && records.length > 0" x-cloak class="barang-masuk-history-table-wrap">
                <table class="barang-masuk-table barang-masuk-history-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="record in records" :key="record.date_key">
                            <tr>
                                <td>
                                    <div class="theme-text text-sm font-semibold" x-text="record.date_label"></div>
                                    <div class="theme-text-muted text-xs">
                                        <span x-text="record.item_count"></span> item
                                    </div>
                                </td>
                                <td>
                                    <button type="button" @click="openDetail(record.date_key)"
                                        class="barang-masuk-view-btn">
                                        Lihat Detail
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!loading && records.length > 0" x-cloak class="barang-masuk-history-cards">
                <template x-for="record in records" :key="record.date_key">
                    <div class="barang-masuk-history-card">
                        <div class="barang-masuk-history-card-date" x-text="record.date_label"></div>
                        <div class="barang-masuk-history-card-meta">
                            <span x-text="record.item_count"></span> item
                        </div>
                        <div class="barang-masuk-history-card-action">
                            <button type="button" @click="openDetail(record.date_key)" class="barang-masuk-view-btn">
                                Lihat Detail
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <x-ui.modal show="detailModalOpen" maxWidth="lg">
            <div class="barang-masuk-detail-modal">
                <div class="barang-masuk-detail-modal-header">
                    <h3 class="barang-masuk-detail-title theme-text">Detail Barang Masuk</h3>
                    <p class="barang-masuk-detail-subtitle" x-text="selectedRecord?.datetime_label"></p>
                </div>

                <div class="barang-masuk-detail-modal-body">
                    <div x-show="detailLoading" class="barang-masuk-loading">
                        Memuat detail...
                    </div>

                    <div x-show="!detailLoading && selectedRecord">
                        <template x-for="item in selectedRecord?.items || []" :key="item.id">
                            <div class="barang-masuk-detail-item">
                                <div>
                                    <div class="barang-masuk-detail-product theme-text" x-text="item.product_name"></div>
                                    <div class="barang-masuk-detail-exp">
                                        exp : <span x-text="item.expired_label"></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="barang-masuk-detail-qty theme-text">
                                        <span x-text="item.quantity"></span> PCS
                                    </div>
                                    <button type="button" @click="requestDeleteItem(item.id)"
                                        class="barang-masuk-delete-btn" :disabled="deleting"
                                        aria-label="Hapus item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916A2.25 2.25 0 0010.5 3h-3a2.25 2.25 0 00-2.25 2.25v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="barang-masuk-detail-modal-footer">
                    <button type="button" @click="closeDetail()" class="barang-masuk-btn">
                        Tutup
                    </button>
                </div>
            </div>
        </x-ui.modal>

        <x-ui.modal show="deleteConfirmOpen" maxWidth="md">
            <div class="confirm-dialog-body">
                <h3 class="confirm-dialog-title">
                    Apakah Anda yakin ingin menghapus riwayat barang masuk ini?
                </h3>

                <div class="confirm-dialog-actions">
                    <button type="button" @click="cancelDelete()" :disabled="deleting"
                        class="confirm-dialog-btn confirm-dialog-cancel">
                        Batal
                    </button>
                    <button type="button" @click="confirmDeleteItem()" :disabled="deleting"
                        class="confirm-dialog-btn confirm-dialog-discard">
                        <span x-show="!deleting">Hapus</span>
                        <span x-show="deleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </x-ui.modal>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/barang-masuk-history.js') }}"></script>
@endpush
