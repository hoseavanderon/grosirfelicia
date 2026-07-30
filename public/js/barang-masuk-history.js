function incomingGoodsHistory() {
    return {
        loading: true,
        records: [],
        detailModalOpen: false,
        deleteConfirmOpen: false,
        selectedRecord: null,
        deletingItemId: null,
        deleting: false,
        detailLoading: false,

        init() {
            this.fetchHistory();
        },

        async fetchHistory() {
            this.loading = true;

            try {
                const response = await fetch("/barang-masuk/history/list", {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load history");
                }

                const data = await response.json();
                this.records = data.records || [];
            } catch (error) {
                console.error(error);
                this.records = [];
                this.toast("error", "Gagal", "Riwayat gagal dimuat.");
            } finally {
                this.loading = false;
            }
        },

        async openDetail(dateKey) {
            this.detailLoading = true;
            this.detailModalOpen = true;

            try {
                const response = await fetch(
                    `/barang-masuk/history/${dateKey}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error("Failed to load detail");
                }

                const data = await response.json();
                this.selectedRecord = data.record;
            } catch (error) {
                console.error(error);
                this.detailModalOpen = false;
                this.toast("error", "Gagal", "Detail gagal dimuat.");
            } finally {
                this.detailLoading = false;
            }
        },

        closeDetail() {
            this.detailModalOpen = false;
            this.selectedRecord = null;
        },

        requestDeleteItem(itemId) {
            if (this.deleting) {
                return;
            }

            this.deletingItemId = itemId;
            this.deleteConfirmOpen = true;
        },

        cancelDelete() {
            this.deleteConfirmOpen = false;
            this.deletingItemId = null;
        },

        updateRecordCount(dateKey, itemCount) {
            if (itemCount <= 0) {
                this.records = this.records.filter(
                    (record) => record.date_key !== dateKey,
                );
                return;
            }

            this.records = this.records.map((record) =>
                record.date_key === dateKey
                    ? { ...record, item_count: itemCount }
                    : record,
            );
        },

        async confirmDeleteItem() {
            if (!this.deletingItemId || this.deleting) {
                return;
            }

            const itemId = this.deletingItemId;
            this.deleting = true;

            try {
                const response = await fetch(`/barang-masuk/logs/${itemId}`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            "Gagal menghapus riwayat barang masuk.",
                    );
                }

                this.deleteConfirmOpen = false;
                this.deletingItemId = null;

                if (data.deleted_date) {
                    this.updateRecordCount(data.date_key, 0);
                    this.closeDetail();
                    this.toast(
                        "success",
                        "Berhasil",
                        "Riwayat barang masuk dihapus.",
                    );
                    return;
                }

                this.selectedRecord = data.record;
                this.updateRecordCount(
                    data.record.date_key,
                    data.record.items.length,
                );
                this.toast(
                    "success",
                    "Berhasil",
                    "Item barang masuk dihapus dan stok diperbarui.",
                );
            } catch (error) {
                console.error(error);
                this.toast(
                    "error",
                    "Gagal",
                    error.message || "Item barang masuk gagal dihapus.",
                );
            } finally {
                this.deleting = false;
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
