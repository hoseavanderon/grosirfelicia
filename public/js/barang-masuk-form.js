function incomingGoodsForm(initialDraft = []) {
    return {
        rows: [],
        products: [],
        productsLoading: true,
        submitting: false,
        invalidRowIds: [],
        submitConfirmOpen: false,
        leaveConfirmOpen: false,
        pendingNavigation: null,
        draftSaveTimer: null,
        navigationBound: false,
        navigationGuardActive: true,
        beforeUnloadHandler: null,
        linkClickHandler: null,
        popStateHandler: null,
        openProductUid: null,
        productQuery: "",

        init() {
            this.rows = this.normalizeDraft(initialDraft);

            if (this.rows.length === 0) {
                this.addRow();
            }

            this.loadProducts();
            this.bindNavigationGuard();
            this.pushHistoryState();
        },

        normalizeDraft(draft) {
            if (!Array.isArray(draft) || draft.length === 0) {
                return [];
            }

            return draft.map((row) => ({
                uid: row.uid || crypto.randomUUID(),
                product_id: row.product_id || null,
                product_name: row.product_name || "",
                expired: row.expired || "",
                quantity: row.quantity ?? "",
            }));
        },

        createRow() {
            return {
                uid: crypto.randomUUID(),
                product_id: null,
                product_name: "",
                expired: "",
                quantity: "",
            };
        },

        addRow() {
            this.rows.push(this.createRow());
            this.scheduleDraftSave();
        },

        removeRow(uid) {
            if (this.rows.length === 1) {
                this.rows = [this.createRow()];
            } else {
                this.rows = this.rows.filter((row) => row.uid !== uid);
            }

            if (this.openProductUid === uid) {
                this.openProductUid = null;
            }

            this.invalidRowIds = this.invalidRowIds.filter((id) => id !== uid);
            this.scheduleDraftSave();
        },

        async loadProducts() {
            this.productsLoading = true;

            try {
                const response = await fetch("/barang-masuk/products", {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load products");
                }

                const data = await response.json();
                this.products = data.products || [];
            } catch (error) {
                console.error(error);
                this.products = [];
                this.toast("error", "Gagal", "Produk gagal dimuat.");
            } finally {
                this.productsLoading = false;
            }
        },

        filteredProducts() {
            const term = this.productQuery.trim().toLowerCase();

            if (!term) {
                return this.products;
            }

            return this.products.filter((product) =>
                product.name.toLowerCase().includes(term),
            );
        },

        toggleProductDropdown(uid) {
            if (this.productsLoading) {
                return;
            }

            this.openProductUid = this.openProductUid === uid ? null : uid;
            this.productQuery = "";

            if (this.openProductUid) {
                this.$nextTick(() => {
                    this.$refs.productSearchInput?.focus();
                });
            }
        },

        closeProductDropdown() {
            this.openProductUid = null;
            this.productQuery = "";
        },

        productLabel(row) {
            return row.product_name || "Pilih produk";
        },

        selectProduct(row, product) {
            row.product_id = product.id;
            row.product_name = product.name;
            this.closeProductDropdown();
            this.onRowChange();
        },

        onRowChange() {
            this.scheduleDraftSave();
        },

        scheduleDraftSave() {
            clearTimeout(this.draftSaveTimer);

            this.draftSaveTimer = setTimeout(() => {
                this.saveDraft();
            }, 250);
        },

        async saveDraft() {
            if (!this.hasDraft()) {
                return;
            }

            try {
                await fetch("/barang-masuk/draft", {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify({
                        rows: this.rows,
                    }),
                });
            } catch (error) {
                console.error(error);
            }
        },

        async discardDraft() {
            try {
                await fetch("/barang-masuk/draft", {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
            } catch (error) {
                console.error(error);
            }
        },

        hasDraft() {
            if (!this.navigationGuardActive) {
                return false;
            }

            return this.rows.some(
                (row) =>
                    row.product_id ||
                    row.expired ||
                    Number(row.quantity) > 0,
            );
        },

        disableNavigationGuard() {
            this.navigationGuardActive = false;
            clearTimeout(this.draftSaveTimer);

            if (this.beforeUnloadHandler) {
                window.removeEventListener(
                    "beforeunload",
                    this.beforeUnloadHandler,
                );
                this.beforeUnloadHandler = null;
            }

            if (this.linkClickHandler) {
                document.removeEventListener(
                    "click",
                    this.linkClickHandler,
                    true,
                );
                this.linkClickHandler = null;
            }

            if (this.popStateHandler) {
                window.removeEventListener("popstate", this.popStateHandler);
                this.popStateHandler = null;
            }
        },

        async markSubmissionComplete() {
            this.rows = [this.createRow()];
            this.invalidRowIds = [];
            this.openProductUid = null;
            this.productQuery = "";
            await this.discardDraft();
            this.disableNavigationGuard();
        },

        bindNavigationGuard() {
            if (this.navigationBound) {
                return;
            }

            this.navigationBound = true;

            this.linkClickHandler = (event) => {
                if (!this.hasDraft()) {
                    return;
                }

                const link = event.target.closest("a[href]");

                if (!link) {
                    return;
                }

                const href = link.getAttribute("href");

                if (
                    !href ||
                    href.startsWith("#") ||
                    href.startsWith("javascript:")
                ) {
                    return;
                }

                const targetUrl = new URL(href, window.location.origin);

                if (targetUrl.pathname === window.location.pathname) {
                    return;
                }

                event.preventDefault();
                this.pendingNavigation = targetUrl.href;
                this.leaveConfirmOpen = true;
            };

            document.addEventListener("click", this.linkClickHandler, true);

            this.beforeUnloadHandler = (event) => {
                if (!this.hasDraft()) {
                    return;
                }

                event.preventDefault();
                event.returnValue = "";
            };

            window.addEventListener("beforeunload", this.beforeUnloadHandler);

            this.popStateHandler = () => {
                if (!this.hasDraft()) {
                    return;
                }

                history.pushState({ incomingGoodsDraft: true }, "");
                this.pendingNavigation = document.referrer || "/home";
                this.leaveConfirmOpen = true;
            };

            window.addEventListener("popstate", this.popStateHandler);
        },

        pushHistoryState() {
            history.pushState({ incomingGoodsDraft: true }, "");
        },

        cancelLeave() {
            this.leaveConfirmOpen = false;
            this.pendingNavigation = null;
        },

        async confirmLeaveSave() {
            await this.saveDraft();
            await this.continueNavigation();
        },

        async confirmLeaveDiscard() {
            await this.discardDraft();
            this.rows = [this.createRow()];
            this.disableNavigationGuard();
            await this.continueNavigation();
        },

        async continueNavigation() {
            const target = this.pendingNavigation;
            this.leaveConfirmOpen = false;
            this.pendingNavigation = null;

            if (target) {
                window.location.href = target;
            }
        },

        validateRows() {
            this.invalidRowIds = [];

            this.rows.forEach((row) => {
                const invalid =
                    !row.product_id ||
                    !row.expired ||
                    !row.quantity ||
                    Number(row.quantity) <= 0;

                if (invalid) {
                    this.invalidRowIds.push(row.uid);
                }
            });

            return this.invalidRowIds.length === 0;
        },

        rowIsInvalid(uid) {
            return this.invalidRowIds.includes(uid);
        },

        requestSubmit() {
            if (!this.validateRows()) {
                this.toast(
                    "warning",
                    "Validasi",
                    "Lengkapi semua baris sebelum memproses barang masuk.",
                );
                return;
            }

            this.submitConfirmOpen = true;
        },

        async processSubmit() {
            if (this.submitting) {
                return;
            }

            if (!this.validateRows()) {
                this.submitConfirmOpen = false;
                return;
            }

            this.submitting = true;

            try {
                const payload = {
                    rows: this.rows.map((row) => ({
                        product_id: row.product_id,
                        expired: row.expired,
                        quantity: Number(row.quantity),
                    })),
                };

                const response = await fetch("/barang-masuk", {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.toast(
                            "error",
                            "Validasi",
                            "Periksa kembali data barang masuk.",
                        );
                        return;
                    }

                    throw new Error(data.message || "Failed to process");
                }

                this.submitConfirmOpen = false;
                this.toast(
                    "success",
                    "Berhasil",
                    "Barang masuk berhasil diproses.",
                );

                await this.markSubmissionComplete();

                window.location.replace(
                    data.redirect || "/barang-masuk/history",
                );
                return;
            } catch (error) {
                console.error(error);
                this.toast(
                    "error",
                    "Gagal",
                    "Barang masuk gagal diproses.",
                );
            } finally {
                this.submitting = false;
            }
        },

        csrfToken() {
            return (
                document.querySelector('meta[name="csrf-token"]')?.content || ""
            );
        },

        toast(type, title, message) {
            window.dispatchEvent(
                new CustomEvent("toast", {
                    detail: { type, title, message },
                }),
            );
        },
    };
}
