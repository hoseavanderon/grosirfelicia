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
        holdTouchStartX: null,
        holdTouchStartY: null,

        isDragging: false,
        sortableInstance: null,

        qty: 0,
        qtyFocusTimer: null,

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

            this.$watch("productModal", (open) => {
                if (!open) {
                    clearTimeout(this.qtyFocusTimer);
                    return;
                }

                this.scheduleQtyFocus();
            });

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

        startHold(product, event = null) {
            if (this.isDragging) {
                return;
            }

            // Ignore non-primary mouse buttons.
            if (event?.type === "mousedown" && event.button !== 0) {
                return;
            }

            // Stop the press from starting a text selection that later
            // spills into the modal while the pointer is still down.
            if (event?.type === "mousedown" && event.cancelable) {
                event.preventDefault();
            }

            this.longPressed = false;
            this.holdTouchStartX = null;
            this.holdTouchStartY = null;

            if (event?.type === "touchstart" && event.touches?.[0]) {
                this.holdTouchStartX = event.touches[0].clientX;
                this.holdTouchStartY = event.touches[0].clientY;
            }

            clearTimeout(this.holdTimer);

            this.holdTimer = setTimeout(() => {
                if (this.isDragging) {
                    return;
                }

                this.longPressed = true;
                this.triggerLongPressHaptic();
                this.openProductDetail(product);
            }, 500);
        },

        onHoldTouchMove(event) {
            if (this.holdTouchStartX === null || this.holdTouchStartY === null) {
                return;
            }

            const touch = event.touches?.[0];

            if (!touch) {
                return;
            }

            const movedX = Math.abs(touch.clientX - this.holdTouchStartX);
            const movedY = Math.abs(touch.clientY - this.holdTouchStartY);

            // Cancel long-press when the user is scrolling.
            if (movedX > 12 || movedY > 12) {
                this.cancelHold();
            }
        },

        cancelHold() {
            clearTimeout(this.holdTimer);
            this.holdTimer = null;
            this.holdTouchStartX = null;
            this.holdTouchStartY = null;
        },

        triggerLongPressHaptic() {
            try {
                if (typeof navigator !== "undefined" && navigator.vibrate) {
                    navigator.vibrate(18);
                }
            } catch {
                // Vibration is optional; ignore unsupported devices.
            }
        },

        openProductDetail(product) {
            this.selectedProduct = product;
            this.resetQtyInput();
            this.productModal = true;
            this.lockSelectionUntilPointerUp();
        },

        getQtyInput() {
            return (
                this.$refs.qtyInput ||
                document.querySelector(".kasir-add-product-modal .qty-input")
            );
        },

        resetQtyInput() {
            this.qty = 0;

            const input = this.getQtyInput();

            if (input) {
                input.value = "0";
            }
        },

        scheduleQtyFocus() {
            const run = () => this.focusQtyInput();

            this.resetQtyInput();

            this.$nextTick(() => {
                this.resetQtyInput();
                run();
                requestAnimationFrame(run);

                const overlay = this.getQtyInput()?.closest(".fixed");

                if (overlay) {
                    const onEnd = () => {
                        overlay.removeEventListener("transitionend", onEnd);
                        run();
                    };

                    overlay.addEventListener("transitionend", onEnd);
                }
            });

            clearTimeout(this.qtyFocusTimer);
            this.qtyFocusTimer = setTimeout(run, 240);
        },

        focusQtyInput() {
            const input = this.getQtyInput();

            if (!input || !this.productModal) {
                return;
            }

            if (String(this.qty) === "0" || this.qty === 0) {
                this.qty = 0;
                input.value = "0";
            }

            input.focus({ preventScroll: true });

            try {
                if (String(input.value) === "0") {
                    input.select();
                } else {
                    const caret = String(input.value).length;
                    input.setSelectionRange(caret, caret);
                }
            } catch {
                // Some browsers reject setSelectionRange on certain input modes.
            }
        },

        onQtyDigitKeydown(event) {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (event.key.length !== 1 || event.key < "0" || event.key > "9") {
                return;
            }

            if (String(this.qty) !== "0") {
                return;
            }

            event.preventDefault();
            this.qty = event.key;
        },

        lockSelectionUntilPointerUp() {
            this.unlockSelectionFromHold?.();

            const clearSelection = () => {
                if (document.activeElement === this.getQtyInput()) {
                    return;
                }

                window.getSelection?.()?.removeAllRanges();
            };

            const preventSelect = (event) => {
                if (event.target?.closest?.(".qty-input")) {
                    return;
                }

                event.preventDefault();
            };

            document.body.classList.add("kasir-hold-modal");
            document.addEventListener("selectstart", preventSelect, true);
            clearSelection();

            const selectionWatch = setInterval(clearSelection, 50);

            let released = false;

            const onPointerUp = () => {
                if (released) {
                    return;
                }

                released = true;
                teardown();

                const refocus = () => this.focusQtyInput();

                refocus();
                requestAnimationFrame(refocus);
                setTimeout(refocus, 0);
                setTimeout(refocus, 50);

                const swallowClick = (event) => {
                    document.removeEventListener("click", swallowClick, true);

                    // Let a release on the input count as a real tap so
                    // mobile keyboards can open from that user gesture.
                    if (event.target?.closest?.(".qty-input")) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    refocus();
                };

                // Swallow the mouseup/touchend click so it cannot highlight
                // or activate modal text/buttons, including iOS delayed clicks.
                document.addEventListener("click", swallowClick, true);
                setTimeout(() => {
                    document.removeEventListener("click", swallowClick, true);
                    refocus();
                }, 400);
            };

            const teardown = () => {
                document.body.classList.remove("kasir-hold-modal");
                document.removeEventListener("selectstart", preventSelect, true);
                window.removeEventListener("pointerup", onPointerUp, true);
                window.removeEventListener("pointercancel", onPointerUp, true);
                window.removeEventListener("mouseup", onPointerUp, true);
                window.removeEventListener("touchend", onPointerUp, true);
                clearInterval(selectionWatch);
                this.unlockSelectionFromHold = null;
            };

            this.unlockSelectionFromHold = teardown;

            window.addEventListener("pointerup", onPointerUp, true);
            window.addEventListener("pointercancel", onPointerUp, true);
            window.addEventListener("mouseup", onPointerUp, true);
            window.addEventListener("touchend", onPointerUp, true);
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

            if (
                this.qty === "" ||
                this.qty === null ||
                this.qty === undefined ||
                !Number.isFinite(qty) ||
                qty <= 0
            ) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            type: "error",
                            title: "Jumlah tidak valid",
                            message: "Masukkan jumlah pcs",
                        },
                    }),
                );

                this.focusQtyInput();
                return;
            }

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
