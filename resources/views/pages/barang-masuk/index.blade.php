@extends('layouts.app')

@section('title', 'Barang Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/barang-masuk.css') }}">
@endpush

@section('content')

    <div x-data="incomingGoodsForm(@js($draft))" lang="id-ID" class="barang-masuk-page space-y-6">

        <div class="barang-masuk-header">
            <h1 class="barang-masuk-title theme-text">Barang Masuk</h1>

            <div class="barang-masuk-actions">
                <button type="button" @click="addRow()" class="barang-masuk-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Baris
                </button>

                <a href="{{ route('barang.masuk.history') }}" class="barang-masuk-btn">
                    Riwayat Barang Masuk
                </a>
            </div>
        </div>

        <div class="surface-card barang-masuk-surface rounded-3xl p-4 shadow-sm sm:p-5">
            <div class="barang-masuk-form-table-wrap">
                <table class="barang-masuk-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Tanggal Expired</th>
                            <th>Jumlah (PCS)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in rows" :key="row.uid">
                            <tr :class="rowIsInvalid(row.uid) ? 'is-invalid' : ''">
                                <td>
                                    <div class="product-select-wrap" data-product-select @click.stop>
                                        <template x-if="productsLoading">
                                            <div class="barang-masuk-products-loading">
                                                <span class="barang-masuk-spinner"></span>
                                                Memuat produk...
                                            </div>
                                        </template>

                                        <template x-if="!productsLoading">
                                            <button type="button"
                                                @click="toggleProductDropdown(row.uid, $event)"
                                                class="product-select-trigger"
                                                :class="rowIsInvalid(row.uid) && !row.product_id ? 'is-invalid' : ''">
                                                <span x-text="productLabel(row)"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <input type="date" lang="id-ID" x-model="row.expired" @change="onRowChange()"
                                        class="barang-masuk-field barang-masuk-field-date"
                                        :class="rowIsInvalid(row.uid) && !row.expired ? 'is-invalid' : ''">
                                </td>
                                <td>
                                    <input type="number" min="1" x-model="row.quantity" @input="onRowChange()"
                                        class="barang-masuk-field"
                                        :class="rowIsInvalid(row.uid) && (!row.quantity || Number(row.quantity) <= 0) ? 'is-invalid' : ''"
                                        placeholder="0">
                                </td>
                                <td>
                                    <button type="button" @click="removeRow(row.uid)" class="barang-masuk-delete-btn"
                                        aria-label="Hapus baris">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916A2.25 2.25 0 0010.5 3h-3a2.25 2.25 0 00-2.25 2.25v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="barang-masuk-cards">
                <template x-for="row in rows" :key="row.uid">
                    <div class="barang-masuk-card" :class="rowIsInvalid(row.uid) ? 'is-invalid' : ''">
                        <div class="barang-masuk-card-header">
                            <button type="button" @click="removeRow(row.uid)" class="barang-masuk-delete-btn"
                                aria-label="Hapus baris">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916A2.25 2.25 0 0010.5 3h-3a2.25 2.25 0 00-2.25 2.25v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>

                        <label class="barang-masuk-card-label">Produk</label>
                        <div class="product-select-wrap" data-product-select @click.stop>
                            <template x-if="productsLoading">
                                <div class="barang-masuk-products-loading">
                                    <span class="barang-masuk-spinner"></span>
                                    Memuat produk...
                                </div>
                            </template>

                            <template x-if="!productsLoading">
                                <button type="button" @click="toggleProductDropdown(row.uid, $event)"
                                    class="product-select-trigger"
                                    :class="rowIsInvalid(row.uid) && !row.product_id ? 'is-invalid' : ''">
                                    <span x-text="productLabel(row)"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </template>
                        </div>

                        <div class="barang-masuk-card-grid">
                            <div>
                                <label class="barang-masuk-card-label">Tanggal Expired</label>
                                <input type="date" lang="id-ID" x-model="row.expired" @change="onRowChange()"
                                    class="barang-masuk-field barang-masuk-field-date"
                                    :class="rowIsInvalid(row.uid) && !row.expired ? 'is-invalid' : ''">
                            </div>
                            <div>
                                <label class="barang-masuk-card-label">Jumlah</label>
                                <input type="number" min="1" x-model="row.quantity" @input="onRowChange()"
                                    class="barang-masuk-field"
                                    :class="rowIsInvalid(row.uid) && (!row.quantity || Number(row.quantity) <= 0) ? 'is-invalid' : ''"
                                    placeholder="0">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="barang-masuk-submit-wrap">
                <button type="button" @click="requestSubmit()" :disabled="submitting"
                    class="barang-masuk-btn barang-masuk-btn-primary">
                    <span x-show="!submitting">Barang Masuk</span>
                    <span x-show="submitting">Memproses...</span>
                </button>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="openProductUid" x-cloak x-transition data-product-select class="product-select-menu is-fixed"
                :style="productMenuStyle" @click.stop>
                <input type="text" x-ref="productSearchInput" x-model="productQuery" class="product-select-search"
                    placeholder="Cari produk...">

                <div class="product-select-options">
                    <template x-for="product in filteredProducts()" :key="product.id">
                        <button type="button" class="product-select-option"
                            :class="openProductRow()?.product_id === product.id ? 'is-active' : ''"
                            @click="selectProduct(openProductRow(), product)" x-text="product.name">
                        </button>
                    </template>

                    <div x-show="filteredProducts().length === 0" class="product-select-empty">
                        Produk tidak ditemukan.
                    </div>
                </div>
            </div>
        </template>

        <x-ui.modal show="submitConfirmOpen" maxWidth="md">
            <div class="confirm-dialog-body">
                <h3 class="confirm-dialog-title">
                    Apakah Anda yakin ingin memproses barang masuk ini?
                </h3>

                <div class="confirm-dialog-actions">
                    <button type="button" @click="submitConfirmOpen = false"
                        class="confirm-dialog-btn confirm-dialog-cancel">
                        Batal
                    </button>
                    <button type="button" @click="processSubmit()" :disabled="submitting"
                        class="confirm-dialog-btn confirm-dialog-confirm">
                        <span x-show="!submitting">Proses</span>
                        <span x-show="submitting">Memproses...</span>
                    </button>
                </div>
            </div>
        </x-ui.modal>

        <x-ui.modal show="leaveConfirmOpen" maxWidth="md">
            <div class="confirm-dialog-body">
                <h3 class="confirm-dialog-title">
                    Anda memiliki draft barang masuk yang belum diproses.
                    <br><br>
                    Simpan draft sebelum meninggalkan halaman?
                </h3>

                <div class="confirm-dialog-actions">
                    <button type="button" @click="cancelLeave()"
                        class="confirm-dialog-btn confirm-dialog-cancel">
                        Batal
                    </button>
                    <button type="button" @click="confirmLeaveDiscard()"
                        class="confirm-dialog-btn confirm-dialog-discard">
                        Buang
                    </button>
                    <button type="button" @click="confirmLeaveSave()"
                        class="confirm-dialog-btn confirm-dialog-confirm">
                        Simpan Draft
                    </button>
                </div>
            </div>
        </x-ui.modal>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/barang-masuk-form.js') }}"></script>
@endpush
