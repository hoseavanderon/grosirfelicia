const ReceiptBuilder = {
    formatRupiah(value) {
        return new Intl.NumberFormat("id-ID").format(value || 0);
    },

    buildReceiptText(transaction) {
        const formatRupiah = this.formatRupiah.bind(this);
        const lines = [];

        lines.push("*Felicia Cell*");
        lines.push(
            "-------------------------------------------------------",
        );

        lines.push(
            `${transaction.date_label || ""} ${transaction.time_label || ""}`,
        );

        lines.push(`Transaksi : ${transaction.trx}`);
        lines.push(`Pelanggan : ${transaction.customer}`);
        lines.push(`No Telp   : ${transaction.phone || "-"}`);

        lines.push(
            "-------------------------------------------------------",
        );

        (transaction.items || []).forEach((item) => {
            lines.push(item.product_name);
            if (item.expired_label) {
                lines.push(`Exp: ${item.expired_label}`);
            }
            lines.push(
                `${item.qty} x Rp ${formatRupiah(item.unit_price)}        Rp ${formatRupiah(item.line_total)}`,
            );
        });

        lines.push(
            "-------------------------------------------------------",
        );

        lines.push(`Total : Rp ${formatRupiah(transaction.amount)}`);

        lines.push(
            "-------------------------------------------------------",
        );
        lines.push("");
        lines.push("Terima Kasih Atas Orderan nya");
        lines.push("F.F");

        return lines.join("\n");
    },
};
