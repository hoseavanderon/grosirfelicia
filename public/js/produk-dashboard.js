function produkDashboard() {
    return {
        bestSellers: [],
        bestSellersLoading: true,

        expiring: [],
        expiringLoading: true,

        critical: [],
        criticalLoading: true,

        copyingExpiring: false,
        copyingCritical: false,

        init() {
            this.loadBestSellers();
            this.loadExpiring();
            this.loadCritical();
        },

        async loadBestSellers() {
            this.bestSellersLoading = true;

            try {
                const response = await fetch("/produk/analytics/best-sellers", {
                    headers: { Accept: "application/json" },
                });

                if (!response.ok) {
                    throw new Error("Failed to load best sellers");
                }

                const data = await response.json();
                this.bestSellers = data.items || [];
            } catch (error) {
                console.error(error);
                this.bestSellers = [];
            } finally {
                this.bestSellersLoading = false;
            }
        },

        async loadExpiring() {
            this.expiringLoading = true;

            try {
                const response = await fetch("/produk/analytics/expiring", {
                    headers: { Accept: "application/json" },
                });

                if (!response.ok) {
                    throw new Error("Failed to load expiring products");
                }

                const data = await response.json();
                this.expiring = data.items || [];
            } catch (error) {
                console.error(error);
                this.expiring = [];
            } finally {
                this.expiringLoading = false;
            }
        },

        async loadCritical() {
            this.criticalLoading = true;

            try {
                const response = await fetch("/produk/analytics/critical", {
                    headers: { Accept: "application/json" },
                });

                if (!response.ok) {
                    throw new Error("Failed to load critical stock");
                }

                const data = await response.json();
                this.critical = data.items || [];
            } catch (error) {
                console.error(error);
                this.critical = [];
            } finally {
                this.criticalLoading = false;
            }
        },

        buildExpiringReport() {
            const lines = ["⚠️ BARANG MENDEKATI EXPIRED", ""];

            if (this.expiring.length === 0) {
                lines.push("Tidak ada barang mendekati expired.");
                return lines.join("\n");
            }

            this.expiring.forEach((item, index) => {
                lines.push(`${index + 1}. ${item.name}`);
                lines.push(`Exp: ${item.expired_label}`);
                lines.push(`Sisa: ${item.stock} PCS`);
                lines.push("");
            });

            return lines.join("\n").trim();
        },

        buildCriticalReport() {
            const lines = ["⚠️ STOK KRITIS", ""];

            if (this.critical.length === 0) {
                lines.push("Tidak ada stok kritis.");
                return lines.join("\n");
            }

            this.critical.forEach((item, index) => {
                lines.push(`${index + 1}. ${item.name}`);
                lines.push(`Sisa: ${item.stock} PCS`);
                lines.push("");
            });

            return lines.join("\n").trim();
        },

        async copyExpiringReport() {
            if (this.copyingExpiring || this.expiring.length === 0) {
                return;
            }

            this.copyingExpiring = true;

            try {
                await navigator.clipboard.writeText(this.buildExpiringReport());
                this.toast(
                    "success",
                    "Berhasil",
                    "Report copied to clipboard.",
                );
            } catch (error) {
                console.error(error);
                this.toast("error", "Gagal", "Gagal menyalin laporan.");
            } finally {
                this.copyingExpiring = false;
            }
        },

        async copyCriticalReport() {
            if (this.copyingCritical || this.critical.length === 0) {
                return;
            }

            this.copyingCritical = true;

            try {
                await navigator.clipboard.writeText(this.buildCriticalReport());
                this.toast(
                    "success",
                    "Berhasil",
                    "Report copied to clipboard.",
                );
            } catch (error) {
                console.error(error);
                this.toast("error", "Gagal", "Gagal menyalin laporan.");
            } finally {
                this.copyingCritical = false;
            }
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
