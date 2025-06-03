@extends('layouts.app')

@section('content')
    <style>
        body {
            overflow-x: hidden;
        }

        .list-group-item {
            transition: background-color 0.3s, transform 0.3s;
            cursor: pointer;
        }

        .list-group-item:hover {
            background-color: #f0f0f0;
            transform: scale(1.01);
        }

        .search-toggle {
            border: none;
            background: transparent;
            color: white;
            font-size: 1.2rem;
            margin-left: 10px;
            cursor: pointer;
        }

        .cart-box {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            background-color: #007bff;
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            z-index: 1000;
            display: none;
            min-height: 50px;
            margin-left: 10px;
            margin-right: 10px;
        }

        .cart-box .cart-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .btn-checkout {
            background-color: #28a745;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            font-size: 1rem;
            width: 100%;
        }

        .btn-checkout:hover {
            background-color: #218838;
        }

        .input-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }

        .select2-selection__rendered {
            text-transform: uppercase;
        }

        .section {
            padding-bottom: 100px !important;
            /* Sesuaikan dengan tinggi cart-box */
        }
    </style>

    <section class="section">
        <div class="row mb-3">
            <div class="col-12">
                <label for="category" class="text-white font-weight-bold mb-1">Pilih Kategori</label>
                <div class="input-group">
                    <select class="form-control" id="categoryBrand" name="categoryBrand">
                        <option value="all">Semua Produk</option>
                        @foreach ($categoriesWithBrands as $item)
                            <option value="{{ $item->category_id }}-{{ $item->brand_id }}">
                                {{ $item->category_name }} {{ $item->brand_name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="text" id="searchInput" class="form-control d-none" placeholder="Cari produk...">

                    <div class="input-group-append">
                        <button class="btn btn-outline-light text-dark" type="button" id="toggleSearch">
                            <i class="fas fa-search" id="searchIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-5">
                <ul class="list-group" id="productList">
                    {{-- Produk akan dimuat dengan AJAX --}}
                </ul>
            </div>
        </div>
    </section>

    <div id="cart" class="cart-box">
        <div class="cart-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-shopping-cart fa-lg mr-2"></i>
                <span id="cart-items">Items: 0</span>
            </div>
            <span id="cart-total">Total: Rp 0</span>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Detail Produk</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="modalProductName"></p>
                    <p id="modalProductPrice"></p>
                    <div class="form-group">
                        <label for="pcsInput">Jumlah (pcs)</label>
                        <input type="number" class="form-control" id="pcsInput" placeholder="Masukkan jumlah">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addToCartFromModal">Tambah ke Keranjang</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cartDetailsModal" tabindex="-1" role="dialog" aria-labelledby="cartDetailsLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Keranjang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="cartDetailsBody">
                    <div class="form-group">
                        <label for="customerSelect">Pilih Pelanggan</label>
                        <select class="form-control text-uppercase" id="customerSelect" name="customer_id" required>
                            <option value="" selected disabled>-- Pilih Pelanggan --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ strtoupper($customer->nama_pelanggan) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="cartProductList">
                        <!-- Ini diisi lewat JS -->
                    </div>

                    <!-- Subtotal -->
                    <div class="mt-3 d-flex justify-content-between" id="cartSubtotalContainer">
                        <h5>Total :</h5>
                        <h5 id="cartSubtotal">Rp 0</h5>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-block tombol_checkout">Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const catBrandSelect = document.getElementById('categoryBrand');
            const searchInput = document.getElementById('searchInput');
            const toggleSearch = document.getElementById('toggleSearch');
            const icon = document.getElementById('searchIcon');
            const productList = document.getElementById('productList');
            const cartBox = document.getElementById('cart');
            const cartItemCount = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');

            let cartItems = JSON.parse(localStorage.getItem('cartItems')) || {};

            updateCartDisplay();

            function updateCartDisplay() {
                let totalItems = 0;
                let totalPrice = 0;

                for (let name in cartItems) {
                    totalItems += cartItems[name].qty;
                    totalPrice += cartItems[name].qty * cartItems[name].price;
                }

                cartItemCount.textContent = `Items: ${totalItems}`;
                cartTotal.textContent = `Total: Rp ${totalPrice.toLocaleString()}`;
                cartBox.style.display = totalItems > 0 ? 'block' : 'none';

                const cartSubtotalEl = document.getElementById('cartSubtotal');
                if (cartSubtotalEl) {
                    cartSubtotalEl.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(totalPrice);
                }

                if (totalItems === 0) {
                    localStorage.removeItem('cartItems');
                } else {
                    localStorage.setItem('cartItems', JSON.stringify(cartItems));
                }
            }

            function saveCartToLocalStorage() {
                localStorage.setItem('cartItems', JSON.stringify(cartItems));
            }

            function loadProducts(filters = {}) {
                const urlParams = new URLSearchParams(filters).toString();

                fetch(`/products/filter?${urlParams}`)
                    .then(res => res.text())
                    .then(html => {
                        productList.innerHTML = html;
                        attachProductClickEvents();
                    });
            }

            catBrandSelect.addEventListener('change', function() {
                loadProducts({
                    catbrand: this.value
                });
            });

            searchInput.addEventListener('input', function() {
                loadProducts({
                    search: this.value
                });
            });

            toggleSearch.addEventListener('click', function() {
                const isSearchActive = catBrandSelect.classList.contains('d-none');
                if (isSearchActive) {
                    catBrandSelect.classList.remove('d-none');
                    searchInput.classList.add('d-none');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-search');
                } else {
                    catBrandSelect.classList.add('d-none');
                    searchInput.classList.remove('d-none');
                    searchInput.focus();
                    icon.classList.remove('fa-search');
                    icon.classList.add('fa-times');
                }
                searchInput.value = '';
                loadProducts();
            });

            function attachProductClickEvents() {
                const items = document.querySelectorAll('.list-group-item');

                items.forEach(item => {
                    let pressTimer;
                    let longPressFired = false;

                    const name = item.getAttribute('data-name');
                    const detailProductId = item.getAttribute('data-detail-product-id');
                    const price = parseInt(item.getAttribute('data-price'));
                    const expired = item.getAttribute('data-expired');
                    const key = `${name}-${expired}`;

                    item.addEventListener('mousedown', function() {
                        pressTimer = setTimeout(() => {
                            document.getElementById('modalProductName').textContent =
                                `Nama Produk: ${name}`;
                            document.getElementById('modalProductPrice').textContent =
                                `Harga: Rp ${price.toLocaleString()}`;
                            document.getElementById('pcsInput').value = '';
                            $('#productModal').modal('show');

                            setTimeout(() => {
                                document.getElementById('pcsInput').focus();
                            }, 500);

                            document.getElementById('addToCartFromModal').onclick =
                                async function() {
                                    const pcs = parseInt(document.getElementById(
                                        'pcsInput').value || '1');
                                    if (!cartItems[key]) {
                                        cartItems[key] = {
                                            detail_product_id: detailProductId,
                                            name,
                                            price,
                                            qty: pcs,
                                            expired
                                        };
                                    } else {
                                        cartItems[key].qty += pcs;
                                        updateCartDisplay();
                                        saveCartToLocalStorage();
                                    }
                                    updateCartDisplay();
                                    $('#productModal').modal('hide');
                                };
                        }, 600);
                    });

                    item.addEventListener('mouseup', () => clearTimeout(pressTimer));
                    item.addEventListener('mouseleave', () => clearTimeout(
                        pressTimer));

                    // Touch events (untuk mobile)
                    item.addEventListener('touchstart', function(e) {
                        pressTimer = setTimeout(() => {
                            if (navigator.vibrate) {
                                navigator.vibrate(50);
                            }

                            document.getElementById('modalProductName').textContent =
                                `Nama Produk: ${name}`;
                            document.getElementById('modalProductPrice').textContent =
                                `Harga: Rp ${price.toLocaleString()}`;
                            document.getElementById('pcsInput').value = '';
                            $('#productModal').modal('show');

                            setTimeout(() => {
                                document.getElementById('pcsInput').focus();
                            }, 500);

                            document.getElementById('addToCartFromModal').onclick =
                                async function() {
                                    const pcs = parseInt(document.getElementById(
                                        'pcsInput').value || '1');
                                    if (!cartItems[key]) {
                                        cartItems[key] = {
                                            detail_product_id: detailProductId,
                                            name,
                                            price,
                                            qty: pcs,
                                            expired
                                        };
                                    } else {
                                        cartItems[key].qty += pcs;
                                        updateCartDisplay();
                                        saveCartToLocalStorage();
                                    }
                                    updateCartDisplay();
                                    $('#productModal').modal('hide');
                                };
                        }, 600);
                    });

                    item.addEventListener('touchend', () => clearTimeout(pressTimer));
                    item.addEventListener('touchcancel', () => clearTimeout(pressTimer));

                    item.addEventListener('click', () => {
                        const key = `${name}-${expired}`;
                        if (!cartItems[key]) {
                            cartItems[key] = {
                                detail_product_id: detailProductId,
                                name,
                                price,
                                qty: 1,
                                expired
                            };
                        } else {
                            cartItems[key].qty += 1;
                        }
                        updateCartDisplay();
                        saveCartToLocalStorage();
                    });
                });

                cartBox.addEventListener('click', function() {
                    let html = '<ul class="list-group">';
                    for (let key in cartItems) {
                        const item = cartItems[key];
                        const subtotal = item.qty * item.price;
                        html += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div style="flex: 1;">
                    <strong class="text-uppercase">${item.name}</strong><br>
                    <small>
                        Harga:
                        <input type="number" class="form-control form-control-sm price-input mt-1" data-key="${key}" value="${item.price}" style="width: 100px; display: inline-block;">
                    </small><br>
                    <small>Expired: (${item.expired})</small>
                </div>
                <div>
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <div class="input-group-prepend">
                            <button class="btn btn-outline-secondary btn-decrease" type="button" data-key="${key}">−</button>
                        </div>
                        <input type="text" class="form-control text-center qty-input" data-key="${key}" value="${item.qty}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary btn-increase" type="button" data-key="${key}">+</button>
                        </div>
                    </div>
                </div>
                <span class="ml-2">Rp ${subtotal.toLocaleString()}</span>
            </li>
        `;
                    }
                    html += '</ul>';
                    document.getElementById('cartProductList').innerHTML = html;

                    const selectElement = document.getElementById('customerSelect');
                    if (selectElement && !selectElement.classList.contains('ts-loaded')) {
                        new TomSelect('#customerSelect', {
                            placeholder: '-- Pilih Pelanggan --',
                            create: false,
                            allowEmptyOption: false,
                            sortField: {
                                field: "text",
                                direction: "asc"
                            }
                        });
                        selectElement.classList.add('ts-loaded'); // tandai sudah terinisialisasi
                    }

                    document.querySelectorAll('.price-input').forEach(input => {
                        input.addEventListener('input', function() {
                            // Hapus semua karakter non-digit dulu
                            let value = this.value.replace(/\D/g, '');

                            if (value === '') {
                                this.value = '';
                                return;
                            }

                            // Format dengan pemisah ribuan
                            this.value = parseInt(value).toLocaleString('id-ID');
                        });

                        input.addEventListener('change', function() {
                            // Saat perubahan selesai, simpan harga ke cartItems tanpa format
                            const key = this.getAttribute('data-key');
                            // Hapus pemisah ribuan sebelum parsing
                            const rawValue = this.value.replace(/\./g, '');
                            const newPrice = parseInt(rawValue);

                            if (isNaN(newPrice) || newPrice <= 0) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Harga tidak valid',
                                    text: 'Harga harus berupa angka positif.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                this.value = cartItems[key].price.toLocaleString('id-ID');
                                return;
                            }

                            cartItems[key].price = newPrice;
                            updateCartDisplay();
                            saveCartToLocalStorage();
                            cartBox.click(); // untuk re-render total
                        });

                        // Inisialisasi format saat load
                        const key = input.getAttribute('data-key');
                        if (cartItems[key]) {
                            input.value = cartItems[key].price.toLocaleString('id-ID');
                        }
                    });

                    document.querySelectorAll('.btn-increase').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const key = this.getAttribute('data-key');
                            cartItems[key].qty += 1;
                            updateCartDisplay();
                            saveCartToLocalStorage();
                            cartBox.click(); // re-render ulang
                        });
                    });

                    document.querySelectorAll('.btn-decrease').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const key = this.getAttribute('data-key');
                            if (cartItems[key].qty <= 1) {
                                Swal.fire({
                                    title: 'Hapus produk?',
                                    text: `Apakah Anda yakin ingin menghapus "${cartItems[key].name}" dari keranjang?`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Ya, hapus',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        delete cartItems[key];
                                        updateCartDisplay();
                                        cartBox.click();
                                    }
                                });
                            } else {
                                cartItems[key].qty -= 1;
                                updateCartDisplay();
                                saveCartToLocalStorage();
                                cartBox.click();
                            }
                        });
                    });

                    document.querySelectorAll('.qty-input').forEach(input => {
                        input.addEventListener('change', function() {
                            const key = this.getAttribute('data-key');
                            const newQty = parseInt(this.value);
                            if (isNaN(newQty) || newQty <= 0) {
                                Swal.fire({
                                    title: 'Hapus produk?',
                                    text: `Apakah Anda yakin ingin menghapus "${cartItems[key].name}" dari keranjang?`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Ya, hapus',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        delete cartItems[key];
                                        updateCartDisplay();
                                        saveCartToLocalStorage();
                                        cartBox.click();
                                    } else {
                                        this.value = cartItems[key]?.qty || 1;
                                    }
                                });
                                return;
                            }

                            cartItems[key].qty = newQty;
                            updateCartDisplay();
                            saveCartToLocalStorage();
                            cartBox.click();
                        });
                    });

                    $('#cartDetailsModal').modal('show');
                });
            }

            $('#cartDetailsModal').on('shown.bs.modal', function() {
                document.querySelector('.tombol_checkout').onclick = function() {
                    const customerId = document.getElementById('customerSelect').value;

                    if (!customerId) {
                        Swal.fire('Peringatan', 'Pilih pelanggan terlebih dahulu.', 'warning');
                        return;
                    }

                    // Tampilkan konfirmasi SweetAlert
                    Swal.fire({
                        title: 'Konfirmasi',
                        text: 'Apakah Anda yakin ingin melanjutkan checkout?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Lanjutkan proses checkout
                            const cartItemsArray = Object.keys(cartItems).map(key => cartItems[
                                key]);

                            fetch('/checkout', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content'),
                                    },
                                    body: JSON.stringify({
                                        customer_id: customerId,
                                        cart_items: cartItemsArray
                                    })
                                })
                                .then(async response => {
                                    const data = await response.json();

                                    if (!response.ok) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Gagal Checkout!',
                                            html: data?.message ||
                                                'Terjadi kesalahan saat memproses checkout.',
                                        });
                                        throw new Error(data?.message ||
                                            'Checkout error');
                                    }

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Transaksi berhasil!',
                                        timer: 1000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.href =
                                            '/riwayat-transaksi';
                                    });

                                    cartItems = {};
                                    localStorage.removeItem('cartItems');
                                    updateCartDisplay();
                                    $('#cartDetailsModal').modal('hide');
                                })
                                .catch(error => {
                                    console.error('Checkout error:', error);
                                });
                        }
                    });
                };
            });

            loadProducts();
        });
    </script>
@endsection
