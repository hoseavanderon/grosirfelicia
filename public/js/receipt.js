const ReceiptBuilder = {
    formatRupiah(value) {
        return new Intl.NumberFormat("id-ID").format(value || 0);
    },

    /**
     * Receipt modal / print / WhatsApp view: prefer product-level aggregation.
     * Falls back to raw items when receipt_items is absent (older payloads).
     */
    forReceipt(transaction) {
        if (!transaction) {
            return transaction;
        }

        const items = Array.isArray(transaction.receipt_items)
            ? transaction.receipt_items
            : transaction.items || [];

        return {
            ...transaction,
            items,
        };
    },

    buildReceiptText(transaction) {
        const formatRupiah = this.formatRupiah.bind(this);
        const view = this.forReceipt(transaction);
        const lines = [];

        lines.push("*Felicia Cell*");
        lines.push(
            "-------------------------------------------------------",
        );

        lines.push(
            `${view.date_label || ""} ${view.time_label || ""}`,
        );

        lines.push(`Transaksi : ${view.trx}`);
        lines.push(`Pelanggan : ${view.customer}`);
        lines.push(`No Telp   : ${view.phone || "-"}`);

        lines.push(
            "-------------------------------------------------------",
        );

        (view.items || []).forEach((item) => {
            lines.push(item.product_name);
            lines.push(
                `${item.qty} x Rp ${formatRupiah(item.unit_price)}        Rp ${formatRupiah(item.line_total)}`,
            );
        });

        lines.push(
            "-------------------------------------------------------",
        );

        lines.push(`Total : Rp ${formatRupiah(view.amount)}`);

        lines.push(
            "-------------------------------------------------------",
        );
        lines.push("");
        lines.push("Terima Kasih Atas Orderan nya");
        lines.push("F.F");

        return lines.join("\n");
    },
};
