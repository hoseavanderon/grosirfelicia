@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/transaksi.css') }}">
@endpush

@section('content')

    <div x-data="transaksiPage()" class="space-y-6">

        <div class="printer-status-bar"
            :class="{
                'is-connected': printerStatus.status === 'connected',
                'is-connecting': printerStatus.status === 'connecting',
                'is-disconnected': printerStatus.status === 'disconnected',
                'is-idle': printerStatus.status === 'idle'
            }">
            <div class="printer-status-main">
                <span class="printer-status-dot" aria-hidden="true"></span>
                <div class="printer-status-copy">
                    <div class="printer-status-label" x-text="printerStatusLabel"></div>
                    <div class="printer-status-hint"
                        x-show="printerStatus.status === 'disconnected'"
                        x-cloak>
                        Nyalakan printer lalu Reconnect. Select Printer hanya untuk ganti perangkat.
                    </div>
                    <div class="printer-status-hint"
                        x-show="printerStatus.status === 'disconnected' && !printerStatus.canAutoReconnect"
                        x-cloak>
                        Browser ini belum mendukung reconnect otomatis (getDevices). Gunakan Select Printer sekali per sesi.
                    </div>
                </div>
            </div>

            <div class="printer-status-actions">
                <button type="button" class="printer-status-btn"
                    x-show="printerStatus.hasSavedPrinter && printerStatus.status !== 'connected'"
                    :disabled="printerBusy || printerStatus.status === 'connecting'"
                    @click="printerConnect(selectedPrinterId || printerStatus.printer?.id)">
                    Reconnect
                </button>
                <button type="button" class="printer-status-btn printer-status-btn-secondary"
                    :disabled="printerBusy"
                    @click="openPrinterManager()">
                    Manage
                </button>
            </div>
        </div>

        <div class="surface-card relative rounded-3xl p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0" @click.outside="calendarOpen = false">
                    <button type="button" @click="calendarOpen = !calendarOpen"
                        class="surface-card inline-flex w-full items-center justify-between gap-3 rounded-3xl px-5 py-3 text-left text-sm shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-md sm:w-auto">
                        <span class="theme-text text-sm font-medium" x-text="formattedRange"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="theme-text h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 7.5V6a2.25 2.25 0 114.5 0v1.5m0 0V6a2.25 2.25 0 114.5 0v1.5M3.75 9h16.5M5.25 19.5h13.5A2.25 2.25 0 0021 17.25V8.25A2.25 2.25 0 0018.75 6H5.25A2.25 2.25 0 003 8.25v8.999A2.25 2.25 0 005.25 19.5z" />
                        </svg>
                    </button>

                    <div x-show="calendarOpen" x-transition x-cloak
                        class="surface-card absolute left-0 z-20 mt-4 w-full max-w-md rounded-3xl p-4 shadow-2xl">
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
                                <button type="button" :class="day ? dayClass(day) : 'calendar-day calendar-day-empty'"
                                    x-text="day ? day.getDate() : ''" @click="selectDay(day)" :disabled="!day">
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <button type="button" @click="resetDate()"
                                class="surface-card rounded-2xl px-4 py-2 text-xs font-semibold">Reset</button>
                            <button type="button" @click="applyDate()"
                                class="rounded-2xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">Terapkan</button>
                        </div>
                    </div>
                </div>

                <button type="button" @click="copySummary()" class="copy-btn" :class="copied ? 'is-copied' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="copy-btn-icon h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5.25H6.75A2.25 2.25 0 004.5 7.5v12a2.25 2.25 0 002.25 2.25h10.5A2.25 2.25 0 0019.5 19.5V7.5a2.25 2.25 0 00-2.25-2.25H15m-6 0V3.75m6 1.5V3.75M8.25 12h8.25M8.25 15h8.25M8.25 18h8.25" />
                    </svg>
                    <span x-text="copied ? 'Tersalin' : 'Salin'"></span>
                </button>
            </div>
        </div>

        <div
            class="surface-card rounded-3xl p-5 shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="theme-text text-sm font-semibold" x-text="summaryTitle"></h2>
                <p class="theme-text-muted text-xs">
                    Uang Di Terima :
                    <span class="theme-text font-semibold">Rp <span x-text="formatRupiah(totalAmount)"></span></span>
                    <span class="text-xs">(<span x-text="transactionCount"></span> Nota)</span>
                </p>
            </div>
        </div>

        <div x-show="loading" x-cloak class="transaksi-loading surface-card rounded-3xl shadow-sm">
            Memuat transaksi...
        </div>

        <div x-show="!loading && transactions.length === 0" x-cloak
            class="transaksi-empty surface-card rounded-3xl shadow-sm">
            <span x-text="isUnsettledMode
                ? 'Tidak ada transaksi yang belum disetor.'
                : 'Tidak ada transaksi pada rentang tanggal ini.'"></span>
        </div>

        <div class="space-y-6">
            <template x-for="(group, groupIndex) in groupedTransactions" :key="group.dateKey">
                <div class="date-group">
                    <div class="date-group-header">
                        <h3 class="date-group-heading" x-text="group.dateLabel"></h3>
                        <button type="button" class="sudah-setor-btn" x-show="canDepositGroup(group)"
                            :disabled="depositingDate === group.dateKey" @click.stop="requestDepositByDate(group.dateKey)">
                            <span x-show="depositingDate !== group.dateKey">SUDAH SETOR</span>
                            <span x-show="depositingDate === group.dateKey">Memproses...</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="transaction in group.transactions" :key="transaction.id">
                            <div @click="openDetail(transaction)"
                                class="transaction-card surface-card rounded-3xl p-4 shadow-sm hover:shadow-lg">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <!-- Nama Toko -->
                                        <p class="theme-text truncate text-sm font-semibold"
                                            style="font-family:'Inter',sans-serif;"
                                            x-text="transaction.customer?.toUpperCase()"></p>

                                        <!-- Total -->
                                        <p class="theme-text mt-1 text-xl font-bold"
                                            style="font-family:'JetBrains Mono',monospace;">
                                            Rp <span x-text="formatRupiah(transaction.amount)"></span>
                                        </p>

                                        <p class="theme-text-muted mt-2 text-xs">
                                            <span x-text="transaction.time"></span>
                                            -
                                            <span x-text="transaction.trx"></span>
                                        </p>
                                    </div>

                                    <div class="payment-badge-wrap" @click.stop>
                                        <button type="button" class="payment-badge payment-badge-btn"
                                            :class="[
                                                transaction.status_class,
                                                paymentMenuOpen === transaction.id ? 'is-open' : '',
                                                paymentUpdating === transaction.id ? 'is-updating' : '',
                                                !isPaymentEditable(transaction) ? 'is-readonly' : '',
                                            ]"
                                            @click="togglePaymentMenu(transaction.id)"
                                            :disabled="paymentUpdating === transaction.id || !isPaymentEditable(transaction)">
                                            <span x-text="transaction.status_label"></span>
                                            <svg x-show="isPaymentEditable(transaction)"
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="payment-badge-chevron h-3.5 w-3.5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>

                                        <div x-show="paymentMenuOpen === transaction.id && isPaymentEditable(transaction)"
                                            x-transition:enter="transition ease-out duration-180"
                                            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-140"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                            @click.outside="closePaymentMenu()" x-cloak class="payment-dropdown">
                                            <template x-for="option in paymentOptions" :key="option.value">
                                                <button type="button" class="payment-dropdown-item"
                                                    :class="transaction.metode_pembayaran === option.value ? 'is-active' : ''"
                                                    @click="requestPaymentUpdate(transaction, option.value)"
                                                    x-text="option.label">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="date-group-divider" x-show="groupIndex < groupedTransactions.length - 1"></div>
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
                    class="receipt-modal relative w-full max-w-lg" id="receipt-print-area">

                    <button type="button" @click="closeDetail()" aria-label="Close"
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

                        <div class="receipt-divider"></div>

                        {{-- View mode: customer info --}}
                        <div class="receipt-section" x-show="!editMode">
                            <div class="receipt-meta-row">
                                <span class="receipt-meta-label">Pelanggan :</span>
                                <span class="receipt-meta-value" x-text="selectedTransaction?.customer"></span>
                            </div>
                            <div class="receipt-meta-row">
                                <span class="receipt-meta-label">Nomor Telepon :</span>
                                <span class="receipt-meta-value" x-text="selectedTransaction?.phone || '-'"></span>
                            </div>
                        </div>

                        {{-- Edit mode: customer search --}}
                        <div class="receipt-section" x-show="editMode" x-cloak>
                            <label class="receipt-edit-label">Pelanggan / Toko</label>
                            <div class="receipt-customer-search" @click.outside="editCustomerDropdownOpen = false">
                                <div x-show="editForm?.selectedCustomer" class="receipt-customer-selected">
                                    <span x-text="editForm?.selectedCustomer?.nama_pelanggan"></span>
                                    <button type="button" @click="clearEditCustomer()" class="receipt-customer-clear">
                                        Ubah
                                    </button>
                                </div>
                                <div x-show="!editForm?.selectedCustomer">
                                    <input type="text" x-model="editForm.customerQuery"
                                        @input="onEditCustomerQueryInput()"
                                        @focus="onEditCustomerSearchFocus()"
                                        placeholder="Cari nama toko..."
                                        class="receipt-edit-input">
                                    <div x-show="editCustomerDropdownOpen" x-cloak class="receipt-customer-dropdown">
                                        <div x-show="editCustomerSearchLoading" class="receipt-customer-dropdown-status">
                                            Mencari...
                                        </div>
                                        <template x-for="customer in editCustomerResults" :key="customer.id">
                                            <button type="button" @click="selectEditCustomer(customer)"
                                                class="receipt-customer-dropdown-item">
                                                <span x-text="customer.nama_pelanggan"></span>
                                            </button>
                                        </template>
                                        <div x-show="!editCustomerSearchLoading && editForm?.customerQuery?.trim() !== '' && editCustomerResults.length === 0"
                                            class="receipt-customer-dropdown-status">
                                            Tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="receipt-divider"></div>

                        {{-- View mode: product-level receipt rows (batches aggregated) --}}
                        <div class="receipt-section" x-show="!editMode">
                            <template x-for="item in selectedTransaction?.receipt_items || []"
                                :key="(item.product_id || item.product_name) + '-' + item.qty + '-' + item.unit_price">
                                <div class="receipt-item">
                                    <div class="receipt-item-top">
                                        <span x-text="item.product_name"></span>
                                        <span>Rp <span x-text="formatRupiah(item.line_total)"></span></span>
                                    </div>
                                    <div class="receipt-item-sub">
                                        <span x-text="item.qty"></span> × Rp
                                        <span x-text="formatRupiah(item.unit_price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Edit mode: items --}}
                        <div class="receipt-section receipt-edit-items" x-show="editMode" x-cloak>
                            <template x-for="(item, index) in editForm?.items || []" :key="item.key">
                                <div class="receipt-edit-item">
                                    <div class="receipt-edit-item-top">
                                        <span class="receipt-edit-item-index" x-text="'#' + (index + 1)"></span>
                                        <button type="button" @click="removeEditItem(index)"
                                            class="receipt-edit-remove"
                                            :disabled="editForm.items.length <= 1">
                                            Hapus
                                        </button>
                                    </div>

                                    <label class="receipt-edit-label">Produk</label>
                                    <div class="receipt-customer-search"
                                        @click.outside="closeProductDropdown(item)">
                                        <div x-show="!item.productSearchMode" class="receipt-customer-selected">
                                            <span x-text="formatProductLabel(item)"></span>
                                            <button type="button" @click="openProductSearch(item)"
                                                class="receipt-customer-clear">
                                                Ubah
                                            </button>
                                        </div>
                                        <div x-show="item.productSearchMode">
                                            <input type="text" x-model="item.productQuery"
                                                :id="'product-search-' + item.key"
                                                @input="onEditProductQueryInput(item)"
                                                @focus="onEditProductSearchFocus(item)"
                                                @keydown="onEditProductKeydown($event, item)"
                                                placeholder="Cari produk..."
                                                class="receipt-edit-input"
                                                autocomplete="off">
                                            <div x-show="item.productDropdownOpen" x-cloak
                                                class="receipt-customer-dropdown">
                                                <template
                                                    x-for="(option, optionIndex) in getFilteredProductOptions(item)"
                                                    :key="option.id">
                                                    <button type="button"
                                                        @mousedown.prevent="selectEditProduct(item, option)"
                                                        class="receipt-customer-dropdown-item"
                                                        :class="optionIndex === item.productHighlightIndex ? 'is-active' : ''">
                                                        <span x-text="formatProductOptionLabel(option)"></span>
                                                    </button>
                                                </template>
                                                <div x-show="item.productQuery.trim() !== '' && getFilteredProductOptions(item).length === 0"
                                                    class="receipt-customer-dropdown-status">
                                                    Produk tidak ditemukan.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="receipt-edit-fields">
                                        <div>
                                            <label class="receipt-edit-label">Jumlah (PCS)</label>
                                            <input type="number" min="1" class="receipt-edit-input"
                                                x-model.number="item.qty" @input="recalculateEditItem(item)">
                                        </div>
                                        <div>
                                            <label class="receipt-edit-label">Harga Jual</label>
                                            <input type="number" min="0" class="receipt-edit-input"
                                                x-model.number="item.unit_price" @input="recalculateEditItem(item)">
                                        </div>
                                    </div>

                                    <div class="receipt-edit-line-total">
                                        Subtotal: Rp <span x-text="formatRupiah(item.line_total)"></span>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="addEditItem()" class="receipt-edit-add-btn">
                                + Tambah Produk
                            </button>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-section">
                            <div class="receipt-total-row">
                                <span>Total:</span>
                                <span>Rp
                                    <span x-text="formatRupiah(editMode ? editTotal : selectedTransaction?.amount)"></span>
                                </span>
                            </div>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-actions">

                            <button type="button" @click="sendWhatsApp()" class="receipt-action-btn"
                                x-show="!editMode">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                                <span>WA</span>
                            </button>

                            <button type="button" @click="editMode ? requestSaveEdit() : enterEditMode()"
                                class="receipt-action-btn"
                                :disabled="editMode ? savingEdit : false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                </svg>
                                <span x-text="editMode ? (savingEdit ? 'Menyimpan...' : 'Simpan') : 'Edit'"></span>
                            </button>

                            <button type="button" @click="requestDeleteTransaction()" class="receipt-action-btn"
                                x-show="!editMode" :disabled="deletingTransaction">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916A2.25 2.25 0 0010.5 3h-3a2.25 2.25 0 00-2.25 2.25v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                <span x-text="deletingTransaction ? 'Menghapus...' : 'Hapus'"></span>
                            </button>

                            <button type="button" @click="printReceipt()" class="receipt-action-btn"
                                x-show="!editMode">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M6.34 18H4.5a2.25 2.25 0 01-2.25-2.25V9.818A2.25 2.25 0 014.5 7.5h15a2.25 2.25 0 012.25 2.25v5.932A2.25 2.25 0 0119.5 18h-1.84M6.34 18v1.125A2.25 2.25 0 008.59 21h6.82a2.25 2.25 0 002.25-2.25V18" />
                                </svg>
                                <span>Print</span>
                            </button>

                            <button type="button" @click="exitEditMode()" class="receipt-action-btn"
                                x-show="editMode" x-cloak>
                                <span>Batal Edit</span>
                            </button>

                        </div>

                    </div>

                </div>

            </div>
        </template>

        <x-ui.modal show="confirmModalOpen" maxWidth="md">
            <div class="confirm-dialog-body">
                <h3 class="confirm-dialog-title" x-text="confirmMessage"></h3>

                <div class="confirm-dialog-actions">
                    <button type="button" @click="cancelConfirm()" class="confirm-dialog-btn confirm-dialog-cancel">
                        Batal
                    </button>
                    <button type="button" @click="executeConfirm()" class="confirm-dialog-btn confirm-dialog-confirm">
                        Ya
                    </button>
                </div>
            </div>
        </x-ui.modal>

        <template x-teleport="body">
            <div x-show="printerModalOpen" x-cloak class="printer-modal-backdrop"
                @keydown.escape.window="printerModalOpen && printerModalCancel()">
                <div class="printer-modal" @click.stop>
                    <div class="printer-modal-header">
                        <div>
                            <h3 class="printer-modal-title">Bluetooth Printer</h3>
                            <p class="printer-modal-subtitle">Kelola koneksi printer thermal</p>
                        </div>
                        <button type="button" class="printer-modal-close" @click="printerModalCancel()"
                            aria-label="Tutup">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="printer-modal-body">
                        <div class="printer-section-label">Previously Used Printers</div>

                        <div x-show="savedPrinters.length === 0" class="printer-empty">
                            Belum ada printer tersimpan. Pair printer baru untuk mulai mencetak.
                        </div>

                        <div class="printer-list" x-show="savedPrinters.length > 0">
                            <template x-for="printer in savedPrinters" :key="printer.id">
                                <div class="printer-item"
                                    :class="selectedPrinterId === printer.id ? 'is-selected' : ''"
                                    @click="selectedPrinterId = printer.id">
                                    <div class="printer-item-main">
                                        <div class="printer-item-name" x-text="printer.name"></div>
                                        <div class="printer-item-meta">
                                            <span
                                                x-text="printer.isConnected ? 'Connected' : (printer.needsReselect ? 'Select printer to continue' : 'Disconnected')"></span>
                                            <span class="printer-item-device"
                                                x-show="printer.deviceName && printer.deviceName !== printer.name"
                                                x-text="'Device: ' + printer.deviceName"></span>
                                        </div>
                                    </div>

                                    <div class="printer-item-actions">
                                        <button type="button"
                                            class="printer-mini-btn printer-mini-btn-primary"
                                            x-show="!printer.isConnected"
                                            :disabled="printerBusy"
                                            @click.stop="printerConnect(printer.id)">
                                            Reconnect
                                        </button>
                                        <button type="button"
                                            class="printer-mini-btn printer-mini-btn-primary"
                                            x-show="printer.isConnected"
                                            disabled>
                                            Connected
                                        </button>
                                        <button type="button" class="printer-mini-btn"
                                            :disabled="printerBusy"
                                            @click.stop="printerSelectChange(printer.id)">
                                            Select Printer
                                        </button>
                                        <button type="button" class="printer-mini-btn"
                                            x-show="printer.isConnected"
                                            :disabled="printerBusy"
                                            @click.stop="printerDisconnect()">
                                            Disconnect
                                        </button>
                                        <button type="button" class="printer-mini-btn"
                                            :disabled="printerBusy"
                                            @click.stop="printerRemove(printer.id)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" class="printer-pair-btn" :disabled="printerBusy"
                            @click="printerPairNew()">
                            <span class="printer-pair-plus">+</span>
                            <span>Select / Pair New Printer</span>
                        </button>
                    </div>

                    <div class="printer-modal-footer">
                        <button type="button" class="printer-footer-btn printer-footer-cancel"
                            :disabled="printerBusy" @click="printerModalCancel()">
                            Cancel
                        </button>
                        <button type="button" class="printer-footer-btn printer-footer-print"
                            :disabled="printerBusy || (!selectedPrinterId && savedPrinters.length === 0)"
                            @click="printerModalPrint()">
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="printerNameModalOpen" x-cloak class="printer-modal-backdrop"
                style="z-index: 140;"
                @keydown.escape.window="printerNameModalOpen && cancelPrinterName()">
                <div class="printer-modal printer-name-modal" @click.stop>
                    <div class="printer-modal-header">
                        <div>
                            <h3 class="printer-modal-title">Name Printer</h3>
                            <p class="printer-modal-subtitle">
                                Beri nama kustom untuk printer ini
                            </p>
                        </div>
                    </div>

                    <div class="printer-modal-body">
                        <div class="printer-name-device">
                            Device selected:
                            <strong x-text="printerNameDeviceLabel"></strong>
                        </div>

                        <label class="printer-name-label" for="printer-custom-name">
                            Custom name
                        </label>
                        <input id="printer-custom-name" type="text" class="printer-name-input"
                            x-model="printerNameDraft"
                            @keydown.enter.prevent="confirmPrinterName()"
                            placeholder="Contoh: Kasir Utama"
                            maxlength="60"
                            x-ref="printerNameInput"
                            x-init="$watch('printerNameModalOpen', (open) => open && $nextTick(() => $refs.printerNameInput?.focus()))" />
                    </div>

                    <div class="printer-modal-footer">
                        <button type="button" class="printer-footer-btn printer-footer-cancel"
                            @click="cancelPrinterName()">
                            Skip
                        </button>
                        <button type="button" class="printer-footer-btn printer-footer-print"
                            @click="confirmPrinterName()">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="printerOverlay" x-cloak class="printer-overlay">
                <div class="printer-overlay-card">
                    <div class="printer-spinner" aria-hidden="true"></div>
                    <div class="printer-overlay-text" x-text="printerOverlay"></div>
                </div>
            </div>
        </template>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/receipt.js') }}"></script>
    <script src="{{ asset('js/thermal-printer.js') }}"></script>
    <script src="{{ asset('js/transaksi.js') }}"></script>
@endpush
