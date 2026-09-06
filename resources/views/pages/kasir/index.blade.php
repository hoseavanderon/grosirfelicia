@extends('layouts.app')

@section('title', 'Kasir')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kasir.css') }}?v={{ filemtime(public_path('css/kasir.css')) }}">
@endpush

@section('content')

    <div x-data="kasirPage(
        @js($categories),
        @js($products)
    )" class="kasir-page">

        {{-- Kategori --}}
        <div class="kasir-toolbar">

            <div class="kasir-toolbar-main">
                <label class="kasir-label">
                    Pilih Kategori
                </label>

                <div class="category-select-wrapper" @click.outside="categoryOpen=false">

                    <button type="button" @click="categoryOpen=!categoryOpen" class="category-select">

                        <span class="category-select-label" x-text="selectedCategory.label"></span>

                        <span class="category-select-icon" :class="categoryOpen ? 'is-open' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>

                    </button>

                    <div x-show="categoryOpen" x-transition class="category-dropdown">

                        <template x-for="category in categories" :key="category.label">

                            <button type="button" @click="selectCategory(category)"
                                class="category-item"
                                :class="selectedCategory.label === category.label ? 'is-active' : ''">

                                <span x-text="category.label"></span>

                            </button>

                        </template>

                    </div>

                </div>
            </div>

            <p x-show="canSortProducts" x-cloak class="kasir-sort-hint">
                Geser ikon <span class="kasir-sort-hint-handle">⋮⋮</span> untuk mengurutkan produk
            </p>

        </div>

        {{-- Produk --}}
        <div class="product-list" x-ref="productList">

            <template x-for="product in visibleProducts" :key="product.product_id">

                <div class="product-group" :data-product-id="product.product_id">

                    {{-- HEADER PRODUCT --}}
                    <div class="product-header">

                        <div class="product-header-row">

                            <div x-show="canSortProducts" class="drag-handle" title="Geser untuk mengurutkan">
                                ⋮⋮
                            </div>

                            <div class="product-header-content">

                                <div class="product-title" x-text="product.name">
                                </div>

                                <div class="product-meta">

                                    <span x-text="product.total_batch"></span>
                                    batch
                                    <span class="product-meta-dot">•</span>
                                    <span x-text="product.total_stock"></span>
                                    pcs

                                </div>

                            </div>

                        </div>

                        <div class="product-price">

                            Rp
                            <span x-text="rupiah(product.price)"></span>

                        </div>

                    </div>

                    {{-- DETAIL PRODUCT --}}
                    <template x-for="detail in product.details.filter(d => d.stock > 0)" :key="detail.id">

                        <div class="product-detail"
                            @mousedown="startHold({ ...product, ...detail }, $event)"
                            @mouseup="cancelHold()"
                            @mouseleave="cancelHold()"
                            @touchstart="startHold({ ...product, ...detail }, $event)"
                            @touchmove="onHoldTouchMove($event)"
                            @touchend="cancelHold()"
                            @touchcancel="cancelHold()"
                            @contextmenu.prevent
                            @selectstart.prevent
                            @dragstart.prevent
                            @click="handleProductClick({ ...product, ...detail })">

                            {{-- Expired --}}
                            <div class="batch-exp">

                                <span class="batch-exp-label">Exp</span>
                                <span x-text="detail.exp"></span>

                            </div>

                            {{-- Status --}}
                            <div class="batch-note"></div>

                            {{-- Stock --}}
                            <div class="batch-stock">

                                <span x-text="detail.stock"></span>
                                <span class="batch-stock-unit">pcs</span>

                            </div>

                        </div>

                    </template>

                </div>

            </template>

        </div>

        <div x-show="selectedCategory.category_id === null && visibleCount < filteredProducts.length"
            x-intersect="loadMore()" class="loading-text">
            Memuat produk...
        </div>

        {{-- Bottom Cart --}}
        <div x-show="cartCount > 0" x-transition class="cart-bar" @click="cartModal=true">

            <div>

                <span x-text="cartCount"></span>
                items

            </div>

            <div>

                Total:
                Rp
                <span x-text="rupiah(cartTotal)"></span>

            </div>

        </div>

        {{-- Modal Tambah Produk --}}
        <x-ui.modal show="productModal" maxWidth="lg">

            <div class="modal-body kasir-add-product-modal">

                <div class="modal-top">

                    <h2 class="text-xl font-semibold">
                        Tambah Produk
                    </h2>

                    <button @click="productModal=false" class="close-btn">
                        ✕
                    </button>

                </div>

                <div class="product-preview">

                    <h3 x-text="selectedProduct?.name" class="product-preview-title"></h3>

                    <p class="product-preview-text">
                        Harga:
                        Rp
                        <span x-text="rupiah(selectedProduct?.price || 0)"></span>
                    </p>

                    <p class="product-preview-text">
                        Stok:
                        <span x-text="selectedProduct?.stock || 0"></span>
                        pcs
                    </p>

                </div>

                <div class="space-y-3">

                    <label class="block text-sm font-medium">
                        Jumlah (Pcs)
                    </label>

                    <input x-model="qty" x-ref="qtyInput" value="0" autocomplete="off"
                        autocorrect="off" autocapitalize="off" spellcheck="false"
                        @keydown="onQtyDigitKeydown" @keydown.enter="addToCart"
                        type="text" inputmode="numeric" enterkeyhint="done" class="qty-input" />

                </div>

                <div class="mt-5">

                    <button @click="addToCart" class="primary-btn w-full">
                        Tambah ke Keranjang
                    </button>

                </div>

            </div>

        </x-ui.modal>

        {{-- Modal Keranjang --}}
        <x-ui.modal show="cartModal" maxWidth="lg">

            <div class="modal-body">

                <div class="modal-top">

                    <h2>Keranjang & Checkout</h2>

                    <button @click="cartModal=false" class="close-btn">
                        ✕
                    </button>

                </div>

                <x-kasir.customer-search />

                <div class="cart-items">

                    <template x-for="item in cart" :key="item.id">

                        <div class="cart-card">

                            <div class="cart-card-top">

                                <div>

                                    <h4 x-text="item.name"></h4>

                                    <x-kasir.price-input />

                                </div>

                                <strong>

                                    Rp
                                    <span x-text="rupiah(item.qty * item.price)"></span>

                                </strong>

                            </div>

                            <div class="qty-control">

                                <button type="button" @click="minusQty(item)">
                                    -
                                </button>

                                <input type="number" class="qty-control-input" min="1" :max="item.stock"
                                    :value="item.qty" @input="updateCartQty(item, $event.target.value)"
                                    @blur="finalizeCartQty(item, $event.target)" @wheel.prevent
                                    @keydown.enter="$event.target.blur()">

                                <button type="button" @click="plusQty(item)">
                                    +
                                </button>

                            </div>

                        </div>

                    </template>

                </div>

                <div class="checkout-footer">

                    <button type="button" @click="openClearCartConfirm()" class="clear-cart-btn"
                        :disabled="cart.length === 0">
                        Clear Keranjang
                    </button>

                    <div class="checkout-footer-total">

                        Total Bayar:
                        <strong>

                            Rp
                            <span x-text="rupiah(cartTotal)"></span>

                        </strong>

                    </div>

                    <button @click="openConfirmCheckout()" class="checkout-btn" :disabled="cart.length === 0">
                        Checkout
                    </button>

                </div>

            </div>

        </x-ui.modal>

        <x-kasir.confirm-checkout />

        <x-kasir.confirm-clear-cart />

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="{{ asset('js/kasir.js') }}?v={{ filemtime(public_path('js/kasir.js')) }}"></script>
@endpush
