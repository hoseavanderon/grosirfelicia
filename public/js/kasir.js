function kasirPage(categories, products) {
    return {
        categoryOpen: false,
        productModal: false,
        cartModal: false,
        confirmCheckoutModal: false,
        confirmClearCartModal: false,
        checkoutLoading: false,

        holdTimer: null,
        longPressed: false,

        isDragging: false,
        sortableInstance: null,

        qty: 1,

        selectedCustomer: null,
        customerQuery: "",
        customerResults: [],
        customerDropdownOpen: false,
        customerSearchTimer: null,
        customerSearchAbort: null,
        customerSearchLoading: false,

        focusedPriceId: null,
        priceInputs: {},

        selectedCategory: {
            label: "SEMUA PRODUK",
            category_id: null,
            brand_id: null,
        },
        selectedProduct: null,

        categories: [
            {
                label: "SEMUA PRODUK",
                category_id: null,
                brand_id: null,
            },

            ...categories,
        ],

        products: products,

        cart: [],
        visibleCount: 10,

        get filteredProducts() {
            const list =
                this.selectedCategory.category_id === null
                    ? this.products
                    : this.products.filter(
                          (product) =>
                              product.category_id ===
                                  this.selectedCategory.category_id &&
                              product.brand_id === this.selectedCategory.brand_id,
                      );

            return [...list].sort(
                (a, b) => (a.sort_order ?? 999999) - (b.sort_order ?? 999999),
            );
        },

        get visibleProducts() {
            if (this.selectedCategory.category_id !== null) {
                return this.filteredProducts;
            }

            return this.filteredProducts.slice(0, this.visibleCount);
        },

        get canSortProducts() {
            return this.selectedCategory.category_id !== null;
        },

        init() {
            this.loadCart();

            this.$nextTick(() => {
                this.initSortable();
            });
        },

        selectCategory(category) {
            this.selectedCategory = category;
            this.visibleCount = 10;
            this.categoryOpen = false;

            this.$nextTick(() => {
                this.initSortable();
            });
        },

        initSortable() {
            if (this.sortableInstance) {
                this.sortableInstance.destroy();
                this.sortableInstance = null;
            }

            if (!this.$refs.productList || typeof Sortable === "undefined") {
                return;
            }

            this.sortableInstance = Sortable.create(this.$refs.productList, {
                animation: 180,
                draggable: ".product-group",
                handle: ".drag-handle",
                disabled: !this.canSortProducts,
                forceFallback: true,
                fallbackOnBody: true,
                ghostClass: "sortable-ghost",
                chosenClass: "sortable-chosen",
                dragClass: "sortable-drag",
                scroll: true,
                scrollSensitivity: 120,
                scrollSpeed: 20,
                bubbleScroll: true,

                onStart: () => {
                    this.isDragging = true;
                },

                onEnd: () => {
                    this.isDragging = false;
                    this.handleSortEnd();
                },
            });
        },

        getDomProductOrder() {
            if (!this.$refs.productList) {
                return [];
            }

            return Array.from(
                this.$refs.productList.querySelectorAll(".product-group"),
            ).map((group) => Number(group.dataset.productId));
        },

        applyProductOrder(orderedIds) {
            const { category_id, brand_id } = this.selectedCategory;

            if (category_id === null || orderedIds.length === 0) {
                return;
            }

            const inCategory = this.products.filter(
                (product) =>
                    product.category_id === category_id &&
                    product.brand_id === brand_id,
            );

            const baseSort = Math.min(
                ...inCategory.map((product) => product.sort_order ?? 999999),
            );

            const productMap = new Map(
                this.products.map((product) => [product.product_id, product]),
            );

            orderedIds.forEach((productId, index) => {
                const product = productMap.get(productId);

                if (
                    product &&
                    product.category_id === category_id &&
                    product.brand_id === brand_id
                ) {
                    product.sort_order = baseSort + index;
                }
            });

            this.products.sort(
                (a, b) => (a.sort_order ?? 999999) - (b.sort_order ?? 999999),
            );
        },

        async handleSortEnd() {
            if (!this.canSortProducts) {
                return;
            }

            const orderedIds = this.getDomProductOrder();

            if (orderedIds.length === 0) {
                return;
            }

            if (this.sortableInstance) {
                this.sortableInstance.destroy();
                this.sortableInstance = null;
            }

            this.applyProductOrder(orderedIds);

            try {
                await this.saveOrder(orderedIds);

                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "success",
                            title: "Berhasil",
                            message: "Urutan produk disimpan",
                        },
                    }),
                );
            } catch (error) {
                console.error(error);

                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Gagal",
                            message: "Gagal menyimpan urutan produk",
                        },
                    }),
                );
            }

            this.$nextTick(() => {
                this.initSortable();
            });
        },

        get cartCount() {
            return this.cart.reduce((total, item) => total + item.qty, 0);
        },

        get cartTotal() {
            return this.cart.reduce(
                (total, item) => total + item.qty * item.price,
                0,
            );
        },

        loadMore() {
            if (
                this.selectedCategory.category_id !== null ||
                this.visibleCount >= this.filteredProducts.length
            ) {
                return;
            }

            this.visibleCount += 10;
        },

        saveCart() {
            localStorage.setItem("kasir_cart", JSON.stringify(this.cart));
        },

        loadCart() {
            const saved = localStorage.getItem("kasir_cart");

            if (saved) {
                this.cart = JSON.parse(saved);
            }
        },

        startHold(product) {
            if (this.isDragging) {
                return;
            }

            this.longPressed = false;

            clearTimeout(this.holdTimer);

            this.holdTimer = setTimeout(() => {
                if (this.isDragging) {
                    return;
                }

                this.longPressed = true;

                this.selectedProduct = product;

                this.qty = 1;

                this.productModal = true;
            }, 500);
        },

        cancelHold() {
            clearTimeout(this.holdTimer);
        },

        handleProductClick(product) {
            if (this.longPressed) {
                this.longPressed = false;
                return;
            }

            this.addDirect(product);
        },

        addDirect(product) {
            const existing = this.cart.find((item) => item.id === product.id);

            if (existing) {
                if (existing.qty + 1 > product.stock) {
                    window.dispatchEvent(
                        new CustomEvent("toast", {
                            detail: {
                                type: "error",
                                title: "Gagal",
                                message: "Stok tidak cukup",
                            },
                        }),
                    );

                    return;
                }

                existing.qty++;
            } else {
                this.cart.push({
                    ...product,
                    qty: 1,
                });
            }

            this.saveCart();
        },

        addToCart() {
            const qty = Number(this.qty);

            if (qty > this.selectedProduct.stock) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Gagal",
                            message: "Stok tidak cukup",
                        },
                    }),
                );

                return;
            }

            const existing = this.cart.find(
                (item) => item.id === this.selectedProduct.id,
            );

            if (existing) {
                if (existing.qty + qty > this.selectedProduct.stock) {
                    window.dispatchEvent(
                        new CustomEvent("toast", {
                            detail: {
                                type: "error",
                                title: "Gagal",
                                message: "Stok tidak cukup",
                            },
                        }),
                    );

                    return;
                }

                existing.qty += qty;
            } else {
                this.cart.push({
                    ...this.selectedProduct,
                    qty: qty,
                });
            }

            this.saveCart();

            this.productModal = false;
        },

        plusQty(item) {
            if (item.qty + 1 > item.stock) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Gagal",
                            message: "Stok tidak cukup",
                        },
                    }),
                );

                return;
            }

            item.qty++;

            this.saveCart();
        },

        minusQty(item) {
            if (item.qty > 1) {
                item.qty--;
            } else {
                this.cart = this.cart.filter(
                    (cartItem) => cartItem.id !== item.id,
                );
            }

            this.saveCart();
        },

        updateCartQty(item, rawValue) {
            if (rawValue === "") {
                return;
            }

            const qty = parseInt(rawValue, 10);

            if (isNaN(qty)) {
                return;
            }

            if (qty < 1) {
                this.cart = this.cart.filter(
                    (cartItem) => cartItem.id !== item.id,
                );
                this.saveCart();
                return;
            }

            if (qty > item.stock) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Gagal",
                            message: "Stok tidak cukup",
                        },
                    }),
                );

                item.qty = item.stock;
                this.saveCart();
                return;
            }

            item.qty = qty;
            this.saveCart();
        },

        finalizeCartQty(item, input) {
            if (input.value === "") {
                input.value = item.qty;
            }
        },

        onCustomerQueryInput() {
            if (
                this.selectedCustomer &&
                this.customerQuery.trim() !==
                    this.selectedCustomer.nama_pelanggan.trim()
            ) {
                this.selectedCustomer = null;
            }

            this.searchCustomers(true);
        },

        onCustomerSearchFocus() {
            if (this.selectedCustomer || this.customerQuery.trim() === "") {
                return;
            }

            this.customerDropdownOpen = true;
            this.searchCustomers(true);
        },

        searchCustomers(immediate = false) {
            clearTimeout(this.customerSearchTimer);

            const query = this.customerQuery.trim();

            if (query === "") {
                this.customerResults = [];
                this.customerDropdownOpen = false;
                this.customerSearchLoading = false;
                this.customerSearchAbort?.abort();
                this.customerSearchAbort = null;
                return;
            }

            this.customerDropdownOpen = true;
            this.customerSearchLoading = true;

            const runSearch = async () => {
                this.customerSearchAbort?.abort();

                const controller = new AbortController();
                this.customerSearchAbort = controller;

                try {
                    const response = await fetch(
                        `/customers/search?q=${encodeURIComponent(query)}`,
                        {
                            signal: controller.signal,
                        },
                    );

                    if (controller.signal.aborted) {
                        return;
                    }

                    if (!response.ok) {
                        this.customerResults = [];
                        return;
                    }

                    this.customerResults = await response.json();
                } catch (error) {
                    if (error.name === "AbortError") {
                        return;
                    }

                    this.customerResults = [];
                } finally {
                    if (this.customerSearchAbort === controller) {
                        this.customerSearchLoading = false;
                        this.customerSearchAbort = null;
                    }
                }
            };

            if (immediate) {
                runSearch();
                return;
            }

            this.customerSearchTimer = setTimeout(runSearch, 100);
        },

        selectCustomer(customer) {
            this.customerSearchAbort?.abort();
            this.customerSearchAbort = null;
            this.customerSearchLoading = false;

            this.selectedCustomer = customer;
            this.customerQuery = customer.nama_pelanggan;
            this.customerResults = [];
            this.customerDropdownOpen = false;

            this.$nextTick(() => {
                this.$refs.customerSelected?.focus();
            });
        },

        clearCustomerSelection() {
            this.customerSearchAbort?.abort();
            this.customerSearchAbort = null;
            this.customerSearchLoading = false;

            this.selectedCustomer = null;
            this.customerQuery = "";
            this.customerResults = [];
            this.customerDropdownOpen = false;

            this.$nextTick(() => {
                this.$refs.customerInput?.focus();
            });
        },

        openConfirmCheckout() {
            if (this.cart.length === 0) {
                return;
            }

            if (!this.selectedCustomer) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Pilih Toko",
                            message: "Pilih nama toko terlebih dahulu",
                        },
                    }),
                );

                return;
            }

            this.customerDropdownOpen = false;
            this.confirmCheckoutModal = true;
        },

        openClearCartConfirm() {
            if (this.cart.length === 0) {
                return;
            }

            this.confirmClearCartModal = true;
        },

        clearCart() {
            this.cart = [];
            this.priceInputs = {};
            this.focusedPriceId = null;

            localStorage.removeItem("kasir_cart");

            this.confirmClearCartModal = false;
            this.confirmCheckoutModal = false;
            this.cartModal = false;
        },

        getPriceInputValue(item) {
            if (
                this.focusedPriceId === item.id &&
                this.priceInputs[item.id] !== undefined
            ) {
                return this.priceInputs[item.id];
            }

            return this.rupiah(item.price);
        },

        onPriceInput(item, rawValue) {
            const digits = String(rawValue).replace(/\D/g, "");

            if (!digits) {
                this.priceInputs[item.id] = "";
                return;
            }

            const price = parseInt(digits, 10);

            this.priceInputs[item.id] = this.rupiah(price);
            item.price = price;
            this.saveCart();
        },

        onPriceBlur(item, input) {
            if (input && input.value.replace(/\D/g, "") === "") {
                input.value = this.rupiah(item.price);
            }

            delete this.priceInputs[item.id];

            if (this.focusedPriceId === item.id) {
                this.focusedPriceId = null;
            }
        },

        removeItem(item) {
            this.cart = this.cart.filter((cartItem) => cartItem.id !== item.id);

            this.saveCart();
        },

        async processCheckout() {
            if (this.checkoutLoading || this.cart.length === 0) {
                return;
            }

            if (!this.selectedCustomer) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Pilih Toko",
                            message: "Pilih nama toko terlebih dahulu",
                        },
                    }),
                );

                return;
            }

            this.checkoutLoading = true;

            try {
                const response = await fetch("/transactions", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        customer_id: this.selectedCustomer.id,
                        items: this.cart.map((item) => ({
                            detail_product_id: item.id,
                            harga_jual: item.price,
                            pcs: item.qty,
                        })),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            Object.values(data.errors || {})
                                .flat()
                                .join(" ") ||
                            "Gagal menyimpan transaksi",
                    );
                }

                this.confirmCheckoutModal = false;
                this.cartModal = false;
                this.cart = [];
                this.selectedCustomer = null;
                this.customerQuery = "";

                localStorage.removeItem("kasir_cart");

                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "success",
                            title: "Berhasil",
                            message: "Transaksi berhasil dibuat",
                        },
                    }),
                );

                setTimeout(() => {
                    window.location.href = "/riwayat-transaksi";
                }, 800);
            } catch (error) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Gagal",
                            message:
                                error.message ||
                                "Terjadi kesalahan saat checkout",
                        },
                    }),
                );
            } finally {
                this.checkoutLoading = false;
            }
        },

        rupiah(value) {
            return new Intl.NumberFormat("id-ID").format(value);
        },

        async saveOrder(productIds) {
            if (this.selectedCategory.category_id === null) {
                return;
            }

            const response = await fetch("/products/reorder", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    products: productIds,
                    category_id: this.selectedCategory.category_id,
                    brand_id: this.selectedCategory.brand_id,
                }),
            });

            if (!response.ok) {
                throw new Error("Failed to save product order");
            }
        },
    };
}
