function jejakProdukPage() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);

    return {
        products: [],
        productsLoading: true,
        productDropdownOpen: false,
        productQuery: "",
        selectedProduct: null,

        fromDate: new Date(weekAgo),
        toDate: new Date(today),
        fromDisplayMonth: new Date(weekAgo.getFullYear(), weekAgo.getMonth(), 1),
        toDisplayMonth: new Date(today.getFullYear(), today.getMonth(), 1),
        fromCalendarOpen: false,
        toCalendarOpen: false,
        pendingFromDate: null,
        pendingToDate: null,

        weekdays: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],

        loading: false,
        filtersReady: false,
        summary: {
            current_stock: 0,
            total_in: 0,
            total_out: 0,
        },
        entries: [],

        init() {
            this.pendingFromDate = new Date(this.fromDate);
            this.pendingToDate = new Date(this.toDate);
            this.loadProducts();

            this.$watch("selectedProduct", () => this.tryAutoSearch());
            this.$watch("fromDate", () => this.tryAutoSearch());
            this.$watch("toDate", () => this.tryAutoSearch());
        },

        get fromMonthName() {
            return this.fromDisplayMonth.toLocaleDateString("id-ID", {
                month: "long",
                year: "numeric",
            });
        },

        get toMonthName() {
            return this.toDisplayMonth.toLocaleDateString("id-ID", {
                month: "long",
                year: "numeric",
            });
        },

        get fromMonthDays() {
            return this.buildMonthDays(this.fromDisplayMonth);
        },

        get toMonthDays() {
            return this.buildMonthDays(this.toDisplayMonth);
        },

        buildMonthDays(displayMonth) {
            const year = displayMonth.getFullYear();
            const month = displayMonth.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            const days = [];

            for (let i = 0; i < firstDay; i++) {
                days.push(null);
            }

            for (let day = 1; day <= totalDays; day++) {
                days.push(new Date(year, month, day));
            }

            return days;
        },

        formatDisplayDate(date) {
            if (!date) {
                return "Pilih tanggal";
            }

            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
            });
        },

        formatApiDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },

        isSameDate(a, b) {
            if (!a || !b) {
                return false;
            }

            return a.toDateString() === b.toDateString();
        },

        dayClass(day, target) {
            const selected =
                target === "from"
                    ? this.pendingFromDate || this.fromDate
                    : this.pendingToDate || this.toDate;

            if (this.isSameDate(day, selected)) {
                return "jejak-calendar-day jejak-calendar-day-selected";
            }

            return "jejak-calendar-day jejak-calendar-day-default";
        },

        toggleFromCalendar() {
            this.toCalendarOpen = false;
            this.closeProductDropdown();

            if (!this.fromCalendarOpen) {
                this.pendingFromDate = new Date(this.fromDate);
                this.fromDisplayMonth = new Date(
                    this.fromDate.getFullYear(),
                    this.fromDate.getMonth(),
                    1,
                );
            }

            this.fromCalendarOpen = !this.fromCalendarOpen;
        },

        toggleToCalendar() {
            this.fromCalendarOpen = false;
            this.closeProductDropdown();

            if (!this.toCalendarOpen) {
                this.pendingToDate = new Date(this.toDate);
                this.toDisplayMonth = new Date(
                    this.toDate.getFullYear(),
                    this.toDate.getMonth(),
                    1,
                );
            }

            this.toCalendarOpen = !this.toCalendarOpen;
        },

        selectDay(target, day) {
            if (!day) {
                return;
            }

            if (target === "from") {
                this.fromDate = new Date(day);
                this.pendingFromDate = new Date(day);
                this.fromDisplayMonth = new Date(
                    day.getFullYear(),
                    day.getMonth(),
                    1,
                );
                this.fromCalendarOpen = false;
            } else {
                this.toDate = new Date(day);
                this.pendingToDate = new Date(day);
                this.toDisplayMonth = new Date(
                    day.getFullYear(),
                    day.getMonth(),
                    1,
                );
                this.toCalendarOpen = false;
            }
        },

        previousMonth(target) {
            const source =
                target === "from" ? this.fromDisplayMonth : this.toDisplayMonth;
            const next = new Date(
                source.getFullYear(),
                source.getMonth() - 1,
                1,
            );

            if (target === "from") {
                this.fromDisplayMonth = next;
            } else {
                this.toDisplayMonth = next;
            }
        },

        nextMonth(target) {
            const source =
                target === "from" ? this.fromDisplayMonth : this.toDisplayMonth;
            const next = new Date(
                source.getFullYear(),
                source.getMonth() + 1,
                1,
            );

            if (target === "from") {
                this.fromDisplayMonth = next;
            } else {
                this.toDisplayMonth = next;
            }
        },

        resetDate(target) {
            const now = new Date();
            now.setHours(0, 0, 0, 0);

            if (target === "from") {
                this.fromDate = new Date(now);
                this.pendingFromDate = new Date(now);
                this.fromDisplayMonth = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    1,
                );
                this.fromCalendarOpen = false;
            } else {
                this.toDate = new Date(now);
                this.pendingToDate = new Date(now);
                this.toDisplayMonth = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    1,
                );
                this.toCalendarOpen = false;
            }
        },

        applyDate(target) {
            if (target === "from") {
                if (this.pendingFromDate) {
                    this.fromDate = new Date(this.pendingFromDate);
                }
                this.fromCalendarOpen = false;
            } else {
                if (this.pendingToDate) {
                    this.toDate = new Date(this.pendingToDate);
                }
                this.toCalendarOpen = false;
            }
        },

        async loadProducts() {
            this.productsLoading = true;

            try {
                const response = await fetch("/jejak-produk/products", {
                    headers: { Accept: "application/json" },
                });

                if (!response.ok) {
                    throw new Error("Failed to load products");
                }

                const data = await response.json();
                this.products = data.products || [];
            } catch (error) {
                console.error(error);
                this.products = [];
                this.showToast(
                    "error",
                    "Gagal",
                    "Daftar produk gagal dimuat.",
                );
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

        toggleProductDropdown() {
            if (this.productsLoading) {
                return;
            }

            this.productDropdownOpen = !this.productDropdownOpen;
            this.productQuery = "";

            if (this.productDropdownOpen) {
                this.$nextTick(() => {
                    this.$refs.productSearchInput?.focus();
                });
            }
        },

        closeProductDropdown() {
            this.productDropdownOpen = false;
            this.productQuery = "";
        },

        selectProduct(product) {
            this.selectedProduct = product;
            this.closeProductDropdown();
        },

        allFiltersSelected() {
            return (
                this.selectedProduct &&
                this.fromDate instanceof Date &&
                this.toDate instanceof Date
            );
        },

        tryAutoSearch() {
            if (!this.allFiltersSelected()) {
                this.filtersReady = false;
                this.entries = [];
                return;
            }

            if (this.fromDate > this.toDate) {
                return;
            }

            this.fetchTrail();
        },

        async fetchTrail() {
            this.loading = true;
            this.filtersReady = false;

            const params = new URLSearchParams({
                product_id: String(this.selectedProduct.id),
                from_date: this.formatApiDate(this.fromDate),
                to_date: this.formatApiDate(this.toDate),
            });

            try {
                const response = await fetch(
                    `/jejak-produk/data?${params.toString()}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );

                if (!response.ok) {
                    throw new Error("Failed to load trail");
                }

                const data = await response.json();
                this.summary = data.summary || {
                    current_stock: 0,
                    total_in: 0,
                    total_out: 0,
                };
                this.entries = data.entries || [];
                this.filtersReady = true;
            } catch (error) {
                console.error(error);
                this.entries = [];
                this.showToast(
                    "error",
                    "Gagal",
                    "Jejak produk gagal dimuat.",
                );
            } finally {
                this.loading = false;
            }
        },

        showToast(type, title, message) {
            window.dispatchEvent(
                new CustomEvent("toast", {
                    detail: { type, title, message },
                }),
            );
        },
    };
}
