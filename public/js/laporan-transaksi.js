function laporanTransaksiPage() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

    return {
        loading: true,
        dataReady: false,
        chartInstance: null,
        chartTransitioning: false,
        activeTab: "sales",

        fromDate: new Date(monthStart),
        toDate: new Date(today),
        fromDisplayMonth: new Date(
            monthStart.getFullYear(),
            monthStart.getMonth(),
            1,
        ),
        toDisplayMonth: new Date(today.getFullYear(), today.getMonth(), 1),
        fromCalendarOpen: false,
        toCalendarOpen: false,
        pendingFromDate: null,
        pendingToDate: null,
        weekdays: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],

        summary: {
            total_transactions: 0,
            total_sales: 0,
            unique_stores: 0,
            total_items_sold: 0,
        },
        displaySummary: {
            total_transactions: 0,
            total_sales: 0,
            unique_stores: 0,
            total_items_sold: 0,
        },
        charts: {
            labels: [],
            sales: [],
            items_sold: [],
            transactions: [],
            categories: { labels: [], values: [] },
        },
        chartStats: {
            sales: { total: 0, average: 0, peak: 0 },
            items_sold: { total: 0, average: 0, peak: 0 },
            transactions: { total: 0, average: 0, peak: 0 },
            categories: { total: 0, average: 0, peak: 0 },
        },
        topCustomers: [],
        bestProducts: [],

        chartTabs: [
            { id: "sales", label: "Penjualan" },
            { id: "items", label: "Barang Terjual" },
            { id: "transactions", label: "Transaksi" },
            { id: "categories", label: "Kategori" },
        ],

        init() {
            this.pendingFromDate = new Date(this.fromDate);
            this.pendingToDate = new Date(this.toDate);

            this.$watch("fromDate", () => this.fetchReport());
            this.$watch("toDate", () => this.fetchReport());

            this.fetchReport();
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

        get hasChartData() {
            if (this.activeTab === "categories") {
                return (this.charts.categories?.values || []).some(
                    (value) => value > 0,
                );
            }

            const key =
                this.activeTab === "sales"
                    ? "sales"
                    : this.activeTab === "items"
                      ? "items_sold"
                      : "transactions";

            return (this.charts[key] || []).some((value) => value > 0);
        },

        get activeChartStats() {
            const key =
                this.activeTab === "sales"
                    ? "sales"
                    : this.activeTab === "items"
                      ? "items_sold"
                      : this.activeTab === "transactions"
                        ? "transactions"
                        : "categories";

            const stats = this.chartStats[key] || {
                total: 0,
                average: 0,
                peak: 0,
            };

            if (this.activeTab === "sales") {
                return {
                    totalLabel: "Rp " + this.formatRupiah(stats.total),
                    averageLabel:
                        "Rp " + this.formatRupiah(Math.round(stats.average)),
                    peakLabel: "Rp " + this.formatRupiah(stats.peak),
                };
            }

            if (this.activeTab === "categories") {
                return {
                    totalLabel: this.formatNumber(stats.total) + " pcs",
                    averageLabel:
                        this.formatNumber(Math.round(stats.average)) + " pcs",
                    peakLabel: this.formatNumber(stats.peak) + " pcs",
                };
            }

            const suffix = this.activeTab === "items" ? " pcs" : "";

            return {
                totalLabel: this.formatNumber(stats.total) + suffix,
                averageLabel:
                    this.formatNumber(Math.round(stats.average)) + suffix,
                peakLabel: this.formatNumber(stats.peak) + suffix,
            };
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

        formatNumber(value) {
            return new Intl.NumberFormat("id-ID").format(Number(value) || 0);
        },

        formatRupiah(value) {
            return new Intl.NumberFormat("id-ID").format(Number(value) || 0);
        },

        formatChartLabel(dateString) {
            const date = new Date(dateString + "T00:00:00");

            return date.toLocaleDateString("id-ID", {
                day: "numeric",
                month: "short",
            });
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
                return "laporan-calendar-day laporan-calendar-day-selected";
            }

            return "laporan-calendar-day laporan-calendar-day-default";
        },

        toggleFromCalendar() {
            this.toCalendarOpen = false;

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

        switchTab(tabId) {
            if (this.activeTab === tabId) {
                return;
            }

            this.chartTransitioning = true;
            this.activeTab = tabId;

            this.$nextTick(() => {
                this.renderChart();
                setTimeout(() => {
                    this.chartTransitioning = false;
                }, 320);
            });
        },

        async fetchReport() {
            if (this.fromDate > this.toDate) {
                return;
            }

            this.loading = true;
            this.dataReady = false;
            this.destroyChart();

            const params = new URLSearchParams({
                from_date: this.formatApiDate(this.fromDate),
                to_date: this.formatApiDate(this.toDate),
            });

            try {
                const response = await fetch(
                    `/laporan-transaksi/data?${params.toString()}`,
                    { headers: { Accept: "application/json" } },
                );

                if (!response.ok) {
                    throw new Error("Failed to load report");
                }

                const data = await response.json();

                this.summary = data.summary || this.summary;
                this.charts = data.charts || this.charts;
                this.chartStats = data.chart_stats || this.chartStats;
                this.topCustomers = data.top_customers || [];
                this.bestProducts = data.best_products || [];
                this.dataReady = true;

                this.animateSummaryCards();

                this.$nextTick(() => {
                    this.renderChart();
                });
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    "Laporan transaksi gagal dimuat.",
                );
            } finally {
                this.loading = false;
            }
        },

        animateSummaryCards() {
            const targets = { ...this.summary };
            const start = { ...this.displaySummary };
            const duration = 380;
            const startTime = performance.now();

            const step = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);

                this.displaySummary = {
                    total_transactions: Math.round(
                        start.total_transactions +
                            (targets.total_transactions -
                                start.total_transactions) *
                                eased,
                    ),
                    total_sales: Math.round(
                        start.total_sales +
                            (targets.total_sales - start.total_sales) * eased,
                    ),
                    unique_stores: Math.round(
                        start.unique_stores +
                            (targets.unique_stores - start.unique_stores) *
                                eased,
                    ),
                    total_items_sold: Math.round(
                        start.total_items_sold +
                            (targets.total_items_sold -
                                start.total_items_sold) *
                                eased,
                    ),
                };

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    this.displaySummary = { ...targets };
                }
            };

            requestAnimationFrame(step);
        },

        getMonochromePalette(count) {
            const palette = [
                "#111111",
                "#444444",
                "#666666",
                "#888888",
                "#aaaaaa",
                "#cccccc",
                "#222222",
                "#555555",
                "#777777",
                "#999999",
            ];

            return Array.from(
                { length: count },
                (_, index) => palette[index % palette.length],
            );
        },

        getThemeColors() {
            const isDark = document.documentElement.classList.contains("dark");
            const text = isDark ? "#ffffff" : "#111111";
            const border = isDark ? "#3f3f46" : "#e4e4e7";

            return {
                text,
                border,
                fill: isDark
                    ? "rgba(255, 255, 255, 0.12)"
                    : "rgba(17, 17, 17, 0.12)",
                bar: isDark
                    ? "rgba(255, 255, 255, 0.72)"
                    : "rgba(17, 17, 17, 0.72)",
                grid: isDark
                    ? "rgba(255, 255, 255, 0.08)"
                    : "rgba(0, 0, 0, 0.08)",
            };
        },

        getChartConfig() {
            const colors = this.getThemeColors();

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 360,
                    easing: "easeOutQuart",
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: "#111111",
                        titleColor: "#ffffff",
                        bodyColor: "#ffffff",
                        borderColor: "#333333",
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                    },
                },
                scales: {},
            };

            if (this.activeTab === "categories") {
                const labels = this.charts.categories?.labels || [];
                const values = this.charts.categories?.values || [];

                return {
                    type: "doughnut",
                    data: {
                        labels,
                        datasets: [
                            {
                                data: values,
                                backgroundColor: this.getMonochromePalette(
                                    values.length,
                                ),
                                borderColor: colors.border,
                                borderWidth: 1,
                                hoverOffset: 6,
                            },
                        ],
                    },
                    options: {
                        ...baseOptions,
                        cutout: "62%",
                        plugins: {
                            ...baseOptions.plugins,
                            legend: {
                                display: values.length > 0,
                                position: "bottom",
                                labels: {
                                    color: colors.text,
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    padding: 14,
                                    font: { size: 11, weight: "600" },
                                },
                            },
                        },
                    },
                };
            }

            const labels = (this.charts.labels || []).map((label) =>
                this.formatChartLabel(label),
            );

            let values = [];
            let type = "line";
            let label = "";

            if (this.activeTab === "sales") {
                values = this.charts.sales || [];
                type = "line";
                label = "Sales";
            } else if (this.activeTab === "items") {
                values = this.charts.items_sold || [];
                type = "bar";
                label = "Items Sold";
            } else {
                values = this.charts.transactions || [];
                type = "line";
                label = "Transactions";
            }

            const dataset =
                type === "bar"
                    ? {
                          label,
                          data: values,
                          backgroundColor: colors.bar,
                          borderColor: colors.text,
                          borderWidth: 1,
                          borderRadius: 8,
                          maxBarThickness: 28,
                      }
                    : {
                          label,
                          data: values,
                          borderColor: colors.text,
                          backgroundColor: colors.fill,
                          borderWidth: 2,
                          fill: true,
                          tension: 0.35,
                          pointRadius: 3,
                          pointHoverRadius: 5,
                          pointBackgroundColor: colors.text,
                          pointBorderColor: colors.text,
                      };

            return {
                type,
                data: {
                    labels,
                    datasets: [dataset],
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: {
                            ticks: {
                                color: colors.text,
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8,
                                font: { size: 11 },
                            },
                            grid: {
                                display: false,
                            },
                            border: {
                                color: colors.border,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: colors.text,
                                font: { size: 11 },
                            },
                            grid: {
                                color: colors.grid,
                            },
                            border: {
                                display: false,
                            },
                        },
                    },
                },
            };
        },

        renderChart() {
            this.destroyChart();

            if (!this.hasChartData || !this.$refs.analyticsChart) {
                return;
            }

            const config = this.getChartConfig();
            const ctx = this.$refs.analyticsChart.getContext("2d");

            this.chartInstance = new Chart(ctx, config);
        },

        destroyChart() {
            if (this.chartInstance) {
                this.chartInstance.destroy();
                this.chartInstance = null;
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
