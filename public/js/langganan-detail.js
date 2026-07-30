function langgananDetailPage(customerId) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const datePicker = createDateRangePicker({
        defaultFrom: new Date(today.getFullYear(), 0, 1),
        defaultTo: new Date(today),
        onApply() {
            this.fetchData();
        },
    });

    return {
        ...datePicker,
        customerId,
        loading: true,
        detailModalOpen: false,
        selectedTransaction: null,
        stats: {
            total_spending: 0,
            total_orders: 0,
            total_items: 0,
            largest_transaction: 0,
        },
        transactions: [],

        init() {
            this.fetchData();
        },

        formatRupiah(value) {
            return ReceiptBuilder.formatRupiah(value);
        },

        async fetchData() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    from: this.formatApiDate(this.fromDate),
                    to: this.formatApiDate(
                        this.toDate ? this.toDate : this.fromDate,
                    ),
                });

                const response = await fetch(
                    `/customers/${this.customerId}/data?${params.toString()}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error("Gagal memuat data pelanggan");
                }

                const data = await response.json();

                this.stats = data.stats || this.stats;
                this.transactions = data.transactions || [];
            } catch (error) {
                console.error(error);
                this.stats = {
                    total_spending: 0,
                    total_orders: 0,
                    total_items: 0,
                    largest_transaction: 0,
                };
                this.transactions = [];
            } finally {
                this.loading = false;
            }
        },

        openDetail(transaction) {
            this.selectedTransaction = transaction;
            this.detailModalOpen = true;
        },

        closeDetail() {
            this.detailModalOpen = false;
            this.selectedTransaction = null;
        },

        sendWhatsApp() {
            if (!this.selectedTransaction?.phone) {
                return;
            }

            const phone = this.selectedTransaction.phone.replace(/\D/g, "");
            const message = ReceiptBuilder.buildReceiptText(
                this.selectedTransaction,
            );
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;

            window.open(url, "_blank");
        },
    };
}
