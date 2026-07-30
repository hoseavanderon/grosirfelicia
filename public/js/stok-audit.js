function stokAuditPage() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return {
        loading: false,
        saving: false,
        copied: false,
        calendarOpen: false,

        selectedDate: new Date(today),
        displayMonth: new Date(today),
        weekdays: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],

        providers: [],
        openProviders: {},

        init() {
            this.loadData();

            document.addEventListener("visibilitychange", () => {
                if (document.visibilityState === "visible") {
                    this.loadData();
                }
            });
        },

        get formattedDate() {
            return this.selectedDate.toLocaleDateString("id-ID", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        },

        get monthName() {
            return this.displayMonth.toLocaleDateString("id-ID", {
                month: "long",
                year: "numeric",
            });
        },

        get monthDays() {
            const year = this.displayMonth.getFullYear();
            const month = this.displayMonth.getMonth();
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

        get storageKey() {
            return "stock_audit_draft";
        },

        csrfToken() {
            return (
                document.querySelector('meta[name="csrf-token"]')?.content || ""
            );
        },

        formatApiDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },

        showToast(type, title, message) {
            window.dispatchEvent(
                new CustomEvent("toast", {
                    detail: { type, title, message },
                }),
            );
        },

        isSameDate(a, b) {
            if (!a || !b) {
                return false;
            }

            return a.toDateString() === b.toDateString();
        },

        dayClass(day) {
            if (this.isSameDate(day, this.selectedDate)) {
                return "stok-calendar-day stok-calendar-day-selected";
            }

            return "stok-calendar-day stok-calendar-day-default";
        },

        selectDay(day) {
            if (!day) {
                return;
            }

            this.selectedDate = new Date(day);
            this.displayMonth = new Date(day.getFullYear(), day.getMonth(), 1);
            this.calendarOpen = false;
            this.loadData();
        },

        previousMonth() {
            this.displayMonth = new Date(
                this.displayMonth.getFullYear(),
                this.displayMonth.getMonth() - 1,
                1,
            );
        },

        nextMonth() {
            this.displayMonth = new Date(
                this.displayMonth.getFullYear(),
                this.displayMonth.getMonth() + 1,
                1,
            );
        },

        resetDate() {
            this.selectedDate = new Date(today);
            this.displayMonth = new Date(today);
            this.calendarOpen = false;
            this.loadData();
        },

        applyDate() {
            this.calendarOpen = false;
            this.loadData();
        },

        isProviderOpen(providerId) {
            return !!this.openProviders[providerId];
        },

        toggleProvider(providerId) {
            this.openProviders[providerId] = !this.openProviders[providerId];
            this.persistLocalProgress();
        },

        providerNeedCheckingCount(provider) {
            return provider.products.filter(
                (product) => product.check_state === "unchecked",
            ).length;
        },

        checkClass(state) {
            return {
                "audit-check-system": state === "system",
                "audit-check-manual": state === "manual",
                "audit-check-unchecked": state === "unchecked",
            };
        },

        productIsOk(product) {
            return (
                product.stock_check_status === 1 ||
                product.check_state === "system"
            );
        },

        toggleProductCheck(product) {
            if (product.check_state === "unchecked") {
                product.check_state = "manual";
            } else if (product.check_state === "manual") {
                product.check_state = "unchecked";
            } else {
                product.check_state = "manual";
            }

            this.persistLocalProgress();
        },

        onPcsInput(product) {
            if (
                product.pcs === "" ||
                product.pcs === null ||
                isNaN(product.pcs)
            ) {
                return;
            }

            product.pcs = Math.max(0, parseInt(product.pcs, 10) || 0);
        },

        checkAll() {
            this.providers.forEach((provider) => {
                provider.products.forEach((product) => {
                    if (product.check_state === "unchecked") {
                        product.check_state = "manual";
                    }
                });
            });

            this.persistLocalProgress();
        },

        collectItems() {
            const items = [];

            this.providers.forEach((provider) => {
                provider.products.forEach((product) => {
                    items.push({
                        product_id: product.product_id,
                        check_state: product.check_state,
                        pcs: Math.max(0, parseInt(product.pcs, 10) || 0),
                    });
                });
            });

            return items;
        },

        persistLocalProgress() {
            const manualProductIds = [];

            this.providers.forEach((provider) => {
                provider.products.forEach((product) => {
                    if (product.check_state === "manual") {
                        manualProductIds.push(product.product_id);
                    }
                });
            });

            localStorage.setItem(
                this.storageKey,
                JSON.stringify({
                    manualProductIds,
                    openProviders: this.openProviders,
                }),
            );
        },

        restoreLocalProgress() {
            const raw = localStorage.getItem(this.storageKey);

            if (!raw) {
                this.openProviders = {};
                return;
            }

            try {
                const saved = JSON.parse(raw);
                const manualProductIds = new Set(saved.manualProductIds || []);

                this.providers.forEach((provider) => {
                    provider.products.forEach((product) => {
                        if (manualProductIds.has(product.product_id)) {
                            product.check_state = "manual";
                        }
                    });
                });

                this.openProviders = saved.openProviders || {};
            } catch (error) {
                console.error(error);
                this.openProviders = {};
            }
        },

        async loadData() {
            this.loading = true;

            try {
                const response = await fetch("/stok/data", {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load stock audit data");
                }

                const data = await response.json();

                this.providers = data.providers || [];
                this.openProviders = {};
                this.restoreLocalProgress();
            } catch (error) {
                console.error(error);
                this.providers = [];
                this.showToast("error", "Gagal", "Gagal memuat data cek stok.");
            } finally {
                this.loading = false;
            }
        },

        async saveProgress() {
            if (this.saving || this.providers.length === 0) {
                return;
            }

            this.saving = true;

            try {
                const response = await fetch("/stok/save", {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify({
                        items: this.collectItems(),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            Object.values(data.errors || {})
                                .flat()
                                .join(" ") ||
                            "Gagal menyimpan progress",
                    );
                }

                localStorage.removeItem(this.storageKey);
                await this.loadData();

                this.showToast(
                    "success",
                    "Berhasil",
                    "Progress cek stok berhasil disimpan.",
                );
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    error.message || "Gagal menyimpan progress.",
                );
            } finally {
                this.saving = false;
            }
        },

        buildWhatsAppReport() {
            const lines = [this.formattedDate, ""];

            this.providers.forEach((provider) => {
                lines.push(provider.provider_name);

                provider.products.forEach((product) => {
                    const pcs = Math.max(0, parseInt(product.pcs, 10) || 0);
                    const okLabel = this.productIsOk(product) ? " ok" : "";

                    lines.push(`${product.name} : ${pcs} pcs${okLabel}`);
                });

                lines.push("");
            });

            return lines.join("\n").trim();
        },

        async copyWhatsAppReport() {
            try {
                const text = this.buildWhatsAppReport();

                if (!text) {
                    return;
                }

                await navigator.clipboard.writeText(text);

                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 1500);
            } catch (error) {
                console.error(error);
                this.showToast("error", "Gagal", "Gagal menyalin laporan.");
            }
        },
    };
}
