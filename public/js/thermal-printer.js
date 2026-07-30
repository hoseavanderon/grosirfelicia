/**
 * Bluetooth thermal printer helper (ESC/POS over Web Bluetooth).
 * Shared by Riwayat Transaksi and halaman_kasir.html.
 */
const ThermalPrinter = (() => {
    const OPTIONAL_SERVICES = [
        "000018f0-0000-1000-8000-00805f9b34fb",
        "0000ff00-0000-1000-8000-00805f9b34fb",
        "0000fee7-0000-1000-8000-00805f9b34fb",
        "6e400001-b5a3-f393-e0a9-e50e24dcca9e",
        "49535343-fe7d-4ae5-8fa9-00009fafaff9",
        "e7810a71-73ae-499d-8c15-faa9aef0c3f2",
    ];

    const KNOWN_WRITE_CHARACTERISTICS = [
        "49535343-8841-43f4-a8d4-ecbe34729bb3",
        "0000ff02-0000-1000-8000-00805f9b34fb",
        "0000fec8-0000-1000-8000-00805f9b34fb",
        "6e400002-b5a3-f393-e0a9-e50e24dcca9e",
        "000018f0-0000-1000-8000-00805f9b34fb",
    ];

    let cachedDevice = null;
    let cachedCharacteristic = null;

    class EscPosEncoder {
        constructor() {
            this.buffer = [];
            this.encoder = new TextEncoder();
        }

        raw(bytes) {
            this.buffer.push(...bytes);
            return this;
        }

        init() {
            return this.raw([0x1b, 0x40]);
        }

        align(mode) {
            return this.raw([0x1b, 0x61, mode]);
        }

        bold(enabled) {
            return this.raw([0x1b, 0x45, enabled ? 1 : 0]);
        }

        text(value) {
            const bytes = this.encoder.encode(String(value));
            this.buffer.push(...bytes);
            return this;
        }

        newline(count = 1) {
            for (let i = 0; i < count; i++) {
                this.buffer.push(0x0a);
            }
            return this;
        }

        divider(width = 32) {
            return this.text("-".repeat(width)).newline();
        }

        cut() {
            return this.raw([0x1d, 0x56, 0x00]);
        }

        build() {
            return new Uint8Array(this.buffer);
        }
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat("id-ID").format(value || 0);
    }

    function padLine(left, right, width = 32) {
        const gap = Math.max(1, width - left.length - right.length);
        return `${left}${" ".repeat(gap)}${right}`;
    }

    function wrapText(text, width = 32) {
        const words = String(text).split(/\s+/);
        const lines = [];
        let current = "";

        words.forEach((word) => {
            const next = current ? `${current} ${word}` : word;
            if (next.length <= width) {
                current = next;
                return;
            }

            if (current) {
                lines.push(current);
            }

            current = word.length > width ? word.slice(0, width) : word;
        });

        if (current) {
            lines.push(current);
        }

        return lines.length ? lines : [""];
    }

    function buildReceiptHtml(transaction, formatAmount = formatRupiah) {
        const itemsHtml = (transaction.items || [])
            .map((item) => {
                const expiredLine = item.expired_label
                    ? `<div class="print-item-exp">Exp: ${item.expired_label}</div>`
                    : "";

                return `
                <div class="print-item">
                    <div class="print-item-name">
                        ${item.product_name}
                    </div>
                    ${expiredLine}

                    <div class="print-item-top">
                        <span>${item.qty} x Rp ${formatAmount(item.unit_price)}</span>
                        <span>Rp ${formatAmount(item.line_total)}</span>
                    </div>
                </div>
            `;
            })
            .join("");

        return `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk ${transaction.trx || ""}</title>

<style>
@page{
    size:58mm auto;
    margin:0;
}

body{
    margin:0;
    padding:8mm 4mm;
    width:58mm;
    font-family:"Courier New",Courier,monospace;
    font-size:11px;
    color:#000;
    background:#fff;
}

.print-title{
    text-align:center;
    font-size:16px;
    font-weight:700;
    margin-bottom:10px;
}

.print-divider{
    border-top:1px solid #000;
    margin:8px 0;
}

.print-row,
.print-item-top,
.print-total{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:8px;
}

.print-item{
    margin-bottom:8px;
}

.print-item-name{
    font-weight:600;
    margin-bottom:2px;
}

.print-item-exp{
    font-size:10px;
    margin-bottom:2px;
}

.print-total{
    font-weight:700;
    font-size:12px;
}

.print-footer{
    margin-top:14px;
    text-align:center;
}

.print-sign{
    margin-top:8px;
    text-align:center;
    font-size:18px;
    font-weight:700;
}
</style>

</head>

<body>

<div class="print-title">
    Felicia Cell
</div>

<div class="print-row">
    <span>${transaction.date_label || ""}</span>
    <span>${transaction.time_label || ""}</span>
</div>

<div class="print-row">
    <span>Transaksi :</span>
    <span>${transaction.trx || ""}</span>
</div>

<div class="print-row">
    <span>Pelanggan :</span>
    <span>${transaction.customer || "-"}</span>
</div>

<div class="print-row">
    <span>No Telp :</span>
    <span>${transaction.phone || "-"}</span>
</div>

<div class="print-divider"></div>

${itemsHtml}

<div class="print-divider"></div>

<div class="print-total">
    <span>Total :</span>
    <span>Rp ${formatAmount(transaction.amount)}</span>
</div>

<div class="print-divider"></div>

<div class="print-footer">
    Terima Kasih Atas Orderan nya
</div>

<div class="print-sign">
    F.F
</div>

<script>
window.onload = function () {
    window.print();
    window.onafterprint = function () {
        window.close();
    };
};
</script>

</body>
</html>`;
    }

    function buildReceiptData(transaction, formatAmount = formatRupiah) {
        const encoder = new EscPosEncoder();

        encoder.init();

        // Header
        encoder.align(1).bold(true).text("Felicia Cell").newline().bold(false);

        encoder.align(0);

        encoder.divider();

        encoder
            .text(
                padLine(
                    transaction.date_label || "",
                    transaction.time_label || "",
                ),
            )
            .newline();

        encoder.text(padLine("Transaksi :", transaction.trx || "")).newline();

        encoder
            .text(padLine("Pelanggan :", transaction.customer || "-"))
            .newline();

        encoder.text(padLine("No Telp :", transaction.phone || "-")).newline();

        encoder.divider();

        // Items
        (transaction.items || []).forEach((item) => {
            encoder.text(item.product_name || "").newline();

            if (item.expired_label) {
                encoder.text(`Exp: ${item.expired_label}`).newline();
            }

            encoder
                .text(
                    padLine(
                        `${item.qty} x Rp ${formatAmount(item.unit_price)}`,
                        `Rp ${formatAmount(item.line_total)}`,
                    ),
                )
                .newline();
        });

        encoder.divider();

        // Total
        encoder
            .bold(true)
            .text(padLine("Total :", `Rp ${formatAmount(transaction.amount)}`))
            .newline();

        encoder.bold(false);

        encoder.divider();

        // Footer
        encoder
            .align(1)
            .newline()
            .text("Terima Kasih Atas Orderan nya")
            .newline(2)
            .bold(true)
            .text("F.F")
            .bold(false)
            .newline(3);

        encoder.cut();

        return encoder.build();
    }
    async function findWritableCharacteristic(server) {
        for (const serviceUuid of OPTIONAL_SERVICES) {
            let service;

            try {
                service = await server.getPrimaryService(serviceUuid);
            } catch {
                continue;
            }

            for (const charUuid of KNOWN_WRITE_CHARACTERISTICS) {
                try {
                    const characteristic =
                        await service.getCharacteristic(charUuid);
                    if (
                        characteristic.properties.write ||
                        characteristic.properties.writeWithoutResponse
                    ) {
                        return characteristic;
                    }
                } catch {
                    // Try next characteristic.
                }
            }

            const characteristics = await service.getCharacteristics();
            const writable = characteristics.find(
                (characteristic) =>
                    characteristic.properties.write ||
                    characteristic.properties.writeWithoutResponse,
            );

            if (writable) {
                return writable;
            }
        }

        const services = await server.getPrimaryServices();
        for (const service of services) {
            const characteristics = await service.getCharacteristics();
            const writable = characteristics.find(
                (characteristic) =>
                    characteristic.properties.write ||
                    characteristic.properties.writeWithoutResponse,
            );

            if (writable) {
                return writable;
            }
        }

        throw new Error("Karakteristik printer tidak ditemukan.");
    }

    async function connect() {
        if (cachedDevice?.gatt?.connected && cachedCharacteristic) {
            return cachedCharacteristic;
        }

        if (!navigator.bluetooth) {
            throw new Error("Browser tidak mendukung Web Bluetooth.");
        }

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: OPTIONAL_SERVICES,
        });

        cachedDevice = device;

        device.addEventListener("gattserverdisconnected", () => {
            cachedCharacteristic = null;
        });

        const server = await device.gatt.connect();
        cachedCharacteristic = await findWritableCharacteristic(server);

        return cachedCharacteristic;
    }

    async function writeData(characteristic, data) {
        const chunkSize = 180;

        for (let offset = 0; offset < data.length; offset += chunkSize) {
            const chunk = data.slice(offset, offset + chunkSize);

            if (characteristic.properties.writeWithoutResponse) {
                await characteristic.writeValueWithoutResponse(chunk);
            } else {
                await characteristic.writeValue(chunk);
            }
        }
    }

    async function print(data) {
        const characteristic = await connect();
        await writeData(characteristic, data);
    }

    async function printTransaction(transaction, formatAmount) {
        const data = buildReceiptData(
            transaction,
            formatAmount || formatRupiah,
        );
        await print(data);
    }

    function openPrintPage(transaction, formatAmount) {
        const printWindow = window.open("", "_blank", "width=320,height=720");

        if (!printWindow) {
            throw new Error("Popup struk diblokir browser.");
        }

        printWindow.document.open();
        printWindow.document.write(
            buildReceiptHtml(transaction, formatAmount || formatRupiah),
        );
        printWindow.document.close();
    }

    async function printTransactionWithFallback(transaction, formatAmount) {
        if (navigator.bluetooth) {
            try {
                await printTransaction(transaction, formatAmount);
                return;
            } catch (error) {
                if (
                    error?.name === "NotFoundError" ||
                    error?.name === "SecurityError"
                ) {
                    throw error;
                }

                console.warn(
                    "Bluetooth print failed, falling back to print page.",
                    error,
                );
            }
        }

        openPrintPage(transaction, formatAmount);
    }

    return {
        printTransaction,
        printTransactionWithFallback,
        openPrintPage,
        buildReceiptData,
        buildReceiptHtml,
    };
})();
