/**
 * Bluetooth thermal printer helper (ESC/POS over Web Bluetooth).
 * Shared by Riwayat Transaksi and other POS pages.
 *
 * Architecture:
 * - Receipt builders (unchanged): buildReceiptData / buildReceiptHtml
 * - Printer Manager: pair / reconnect / connect / disconnect / storage
 * - Print orchestration: printTransaction / printTransactionWithFallback
 * - UI bridge: Alpine registers handlers for modal, overlay, and toasts
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

    const STORAGE_KEY = "grosirfelicia.thermalPrinters";

    let cachedDevice = null;
    let cachedCharacteristic = null;
    let cachedPrinterMeta = null;
    const disconnectBoundDevices = new WeakSet();

    let uiHandlers = {
        openModal: null,
        closeModal: null,
        setOverlay: null,
        clearOverlay: null,
        refreshPrinters: null,
    };

    let pendingPrint = null;

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

    /* ---------------------------------
     * UI helpers
     * --------------------------------- */

    function toast(type, title, message = "") {
        window.dispatchEvent(
            new CustomEvent("toast", {
                detail: { type, title, message },
            }),
        );
    }

    function registerUi(handlers = {}) {
        uiHandlers = {
            ...uiHandlers,
            ...handlers,
        };
    }

    function setOverlay(message) {
        if (typeof uiHandlers.setOverlay === "function") {
            uiHandlers.setOverlay(message);
            return;
        }

        // Fallback: still notify via toast so UI is not silent.
        toast("info", message);
    }

    function clearOverlay() {
        if (typeof uiHandlers.clearOverlay === "function") {
            uiHandlers.clearOverlay();
        }
    }

    function waitForPaint() {
        return new Promise((resolve) => {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    setTimeout(resolve, 40);
                });
            });
        });
    }

    async function showOverlay(message) {
        setOverlay(message);
        await waitForPaint();
    }

    async function holdOverlay(ms = 450) {
        await new Promise((resolve) => setTimeout(resolve, ms));
    }

    function openModal() {
        refreshPrinterList();

        if (typeof uiHandlers.openModal === "function") {
            uiHandlers.openModal();
        }
    }

    function closeModal() {
        if (typeof uiHandlers.closeModal === "function") {
            uiHandlers.closeModal();
        }
    }

    function refreshPrinterList() {
        if (typeof uiHandlers.refreshPrinters === "function") {
            uiHandlers.refreshPrinters(getSavedPrinters());
        }
    }

    function assertBluetoothAvailable() {
        if (!navigator.bluetooth) {
            toast("error", "Bluetooth unavailable.", "Browser tidak mendukung Web Bluetooth.");
            const error = new Error("Browser tidak mendukung Web Bluetooth.");
            error.name = "BluetoothUnavailable";
            throw error;
        }
    }

    /* ---------------------------------
     * Storage
     * --------------------------------- */

    function readStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return { printers: [], lastPrinterId: null };
            }

            const parsed = JSON.parse(raw);

            return {
                printers: Array.isArray(parsed.printers) ? parsed.printers : [],
                lastPrinterId: parsed.lastPrinterId || null,
            };
        } catch {
            return { printers: [], lastPrinterId: null };
        }
    }

    function writeStorage(data) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        refreshPrinterList();
    }

    function getSavedPrinters() {
        const data = readStorage();
        const connectedId = isConnected() ? cachedPrinterMeta?.id : null;

        return data.printers
            .slice()
            .sort((a, b) => {
                if (a.id === data.lastPrinterId) {
                    return -1;
                }

                if (b.id === data.lastPrinterId) {
                    return 1;
                }

                return String(b.lastUsedAt || "").localeCompare(
                    String(a.lastUsedAt || ""),
                );
            })
            .map((printer) => ({
                ...printer,
                isConnected: connectedId === printer.id,
                isLastUsed: data.lastPrinterId === printer.id,
            }));
    }

    function getLastPrinter() {
        const data = readStorage();

        if (!data.printers.length) {
            return null;
        }

        if (data.lastPrinterId) {
            const last = data.printers.find(
                (printer) => printer.id === data.lastPrinterId,
            );

            if (last) {
                return last;
            }
        }

        return data.printers[0];
    }

    function savePrinter(printer) {
        const data = readStorage();
        const index = data.printers.findIndex(
            (item) =>
                item.id === printer.id || item.deviceId === printer.deviceId,
        );

        const record = {
            id:
                printer.id ||
                (typeof crypto !== "undefined" && crypto.randomUUID
                    ? crypto.randomUUID()
                    : `printer-${Date.now()}`),
            name: printer.name || "Bluetooth Printer",
            deviceId: printer.deviceId,
            serviceUuid: printer.serviceUuid || null,
            characteristicUuid: printer.characteristicUuid || null,
            lastUsedAt: new Date().toISOString(),
        };

        if (index >= 0) {
            data.printers[index] = {
                ...data.printers[index],
                ...record,
                id: data.printers[index].id,
            };
            data.lastPrinterId = data.printers[index].id;
            cachedPrinterMeta = data.printers[index];
        } else {
            data.printers.push(record);
            data.lastPrinterId = record.id;
            cachedPrinterMeta = record;
        }

        writeStorage(data);

        return cachedPrinterMeta;
    }

    function touchPrinter(printerId) {
        const data = readStorage();
        const index = data.printers.findIndex((item) => item.id === printerId);

        if (index < 0) {
            return;
        }

        data.printers[index].lastUsedAt = new Date().toISOString();
        data.lastPrinterId = printerId;
        writeStorage(data);
        cachedPrinterMeta = data.printers[index];
    }

    function removePrinter(printerId) {
        const data = readStorage();
        const removed = data.printers.find((item) => item.id === printerId);

        data.printers = data.printers.filter((item) => item.id !== printerId);

        if (data.lastPrinterId === printerId) {
            data.lastPrinterId = data.printers[0]?.id || null;
        }

        writeStorage(data);

        if (
            cachedPrinterMeta?.id === printerId ||
            (removed && cachedDevice?.id === removed.deviceId)
        ) {
            disconnectPrinter({ silent: true });
        }

        toast("info", "Printer removed.", removed?.name || "");
    }

    /* ---------------------------------
     * Bluetooth core
     * --------------------------------- */

    function isConnected() {
        return Boolean(cachedDevice?.gatt?.connected && cachedCharacteristic);
    }

    function getConnectionState() {
        return {
            connected: isConnected(),
            printer: cachedPrinterMeta,
            deviceName: cachedDevice?.name || cachedPrinterMeta?.name || null,
        };
    }

    async function findWritableCharacteristic(server, preferred = {}) {
        if (preferred.serviceUuid && preferred.characteristicUuid) {
            try {
                const service = await server.getPrimaryService(
                    preferred.serviceUuid,
                );
                const characteristic = await service.getCharacteristic(
                    preferred.characteristicUuid,
                );

                if (
                    characteristic.properties.write ||
                    characteristic.properties.writeWithoutResponse
                ) {
                    return {
                        characteristic,
                        serviceUuid: preferred.serviceUuid,
                        characteristicUuid: preferred.characteristicUuid,
                    };
                }
            } catch {
                // Fall through to discovery.
            }
        }

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
                        return {
                            characteristic,
                            serviceUuid,
                            characteristicUuid: charUuid,
                        };
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
                return {
                    characteristic: writable,
                    serviceUuid,
                    characteristicUuid: writable.uuid,
                };
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
                return {
                    characteristic: writable,
                    serviceUuid: service.uuid,
                    characteristicUuid: writable.uuid,
                };
            }
        }

        throw new Error("Karakteristik printer tidak ditemukan.");
    }

    function bindDeviceDisconnect(device) {
        if (disconnectBoundDevices.has(device)) {
            return;
        }

        disconnectBoundDevices.add(device);

        device.addEventListener("gattserverdisconnected", () => {
            cachedCharacteristic = null;

            if (cachedPrinterMeta) {
                refreshPrinterList();
            }
        });
    }

    async function connectToDevice(device, savedPrinter = null) {
        cachedDevice = device;
        bindDeviceDisconnect(device);

        const server = device.gatt.connected
            ? device.gatt
            : await device.gatt.connect();

        const found = await findWritableCharacteristic(server, {
            serviceUuid: savedPrinter?.serviceUuid,
            characteristicUuid: savedPrinter?.characteristicUuid,
        });

        cachedCharacteristic = found.characteristic;

        const meta = savePrinter({
            id: savedPrinter?.id,
            name: device.name || savedPrinter?.name || "Bluetooth Printer",
            deviceId: device.id,
            serviceUuid: found.serviceUuid,
            characteristicUuid: found.characteristicUuid,
        });

        return {
            characteristic: cachedCharacteristic,
            printer: meta,
        };
    }

    async function findGrantedDevice(deviceId) {
        if (!navigator.bluetooth?.getDevices) {
            return null;
        }

        const devices = await navigator.bluetooth.getDevices();

        return devices.find((device) => device.id === deviceId) || null;
    }

    async function pairPrinter() {
        assertBluetoothAvailable();

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: OPTIONAL_SERVICES,
        });

        await showOverlay("Connecting to printer...");

        try {
            const result = await connectToDevice(device);
            await holdOverlay(350);
            toast("success", "Printer connected.", result.printer.name);
            return result.printer;
        } finally {
            clearOverlay();
        }
    }

    async function reconnectPrinter(savedPrinter = null) {
        assertBluetoothAvailable();

        const printer = savedPrinter || getLastPrinter();

        if (!printer?.deviceId) {
            const error = new Error("Printer not found.");
            error.name = "PrinterNotFound";
            throw error;
        }

        if (
            cachedDevice?.id === printer.deviceId &&
            cachedDevice?.gatt?.connected &&
            cachedCharacteristic
        ) {
            touchPrinter(printer.id);
            cachedPrinterMeta = printer;
            return printer;
        }

        let device = null;

        if (cachedDevice?.id === printer.deviceId) {
            device = cachedDevice;
        } else {
            device = await findGrantedDevice(printer.deviceId);
        }

        if (!device) {
            const error = new Error("Printer not found.");
            error.name = "PrinterNotFound";
            throw error;
        }

        const result = await connectToDevice(device, printer);
        return result.printer;
    }

    async function connectPrinter(printerId = null) {
        assertBluetoothAvailable();

        const printers = readStorage().printers;
        const printer = printerId
            ? printers.find((item) => item.id === printerId)
            : getLastPrinter();

        if (!printer) {
            const error = new Error("Printer not found.");
            error.name = "PrinterNotFound";
            throw error;
        }

        await showOverlay("Connecting to printer...");

        try {
            const connected = await reconnectPrinter(printer);
            await holdOverlay(350);
            toast("success", "Printer connected.", connected.name);
            return connected;
        } catch (error) {
            toast(
                "error",
                "Connection failed.",
                error?.message || "Tidak dapat terhubung ke printer.",
            );
            throw error;
        } finally {
            clearOverlay();
        }
    }

    async function disconnectPrinter({ silent = false } = {}) {
        try {
            if (cachedDevice?.gatt?.connected) {
                cachedDevice.gatt.disconnect();
            }
        } catch {
            // Ignore disconnect errors.
        }

        cachedCharacteristic = null;
        cachedDevice = null;
        cachedPrinterMeta = null;
        refreshPrinterList();

        if (!silent) {
            toast("info", "Printer disconnected.");
        }
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

    async function ensureCharacteristic() {
        if (isConnected()) {
            return cachedCharacteristic;
        }

        const lastPrinter = getLastPrinter();

        if (lastPrinter) {
            await reconnectPrinter(lastPrinter);
            return cachedCharacteristic;
        }

        throw Object.assign(new Error("Printer not found."), {
            name: "PrinterNotFound",
        });
    }

    async function sendPrintData(data) {
        const characteristic = await ensureCharacteristic();
        await writeData(characteristic, data);
    }

    async function printTransaction(transaction, formatAmount) {
        const data = buildReceiptData(
            transaction,
            formatAmount || formatRupiah,
        );

        toast("info", "Printing...");
        await showOverlay("Printing...");

        try {
            await sendPrintData(data);
            await holdOverlay(350);
            toast("success", "Print completed.");
        } finally {
            clearOverlay();
        }
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

    function clearPendingPrint(error = null) {
        if (!pendingPrint) {
            return;
        }

        const pending = pendingPrint;
        pendingPrint = null;

        if (error) {
            pending.reject(error);
        } else {
            pending.resolve();
        }
    }

    function beginPendingPrint(transaction, formatAmount) {
        return new Promise((resolve, reject) => {
            pendingPrint = {
                transaction,
                formatAmount: formatAmount || formatRupiah,
                resolve,
                reject,
            };

            openModal();
        });
    }

    async function printPendingIfAny() {
        if (!pendingPrint) {
            return;
        }

        const { transaction, formatAmount } = pendingPrint;

        closeModal();

        try {
            await printTransaction(transaction, formatAmount);
            clearPendingPrint();
        } catch (error) {
            clearPendingPrint(error);
            throw error;
        }
    }

    async function pairAndPrint() {
        try {
            await pairPrinter();
            await printPendingIfAny();
        } catch (error) {
            clearOverlay();

            if (
                error?.name === "NotFoundError" ||
                error?.name === "SecurityError"
            ) {
                toast("info", "Pairing dibatalkan.");
                return;
            }

            toast(
                "error",
                "Connection failed.",
                error?.message || "Gagal memasangkan printer.",
            );
            throw error;
        }
    }

    async function connectSavedAndPrint(printerId) {
        try {
            await connectPrinter(printerId);
            await printPendingIfAny();
        } catch (error) {
            if (error?.name === "PrinterNotFound") {
                toast("error", "Printer not found.");
            }

            // Keep modal open so user can pair again.
            throw error;
        }
    }

    async function cancelPrinterModal() {
        closeModal();
        clearOverlay();

        const error = new Error("Print dibatalkan.");
        error.name = "PrintCancelled";
        clearPendingPrint(error);
    }

    async function printFromModal() {
        const lastPrinter = getLastPrinter();

        if (!lastPrinter && !isConnected()) {
            toast("info", "Pilih atau pasangkan printer terlebih dahulu.");
            return;
        }

        try {
            if (!isConnected()) {
                await connectPrinter(lastPrinter.id);
            }

            await printPendingIfAny();
        } catch (error) {
            if (error?.name === "PrintCancelled") {
                return;
            }

            toast(
                "error",
                "Connection failed.",
                error?.message || "Gagal mencetak.",
            );
        }
    }

    async function printTransactionWithFallback(transaction, formatAmount) {
        if (!navigator.bluetooth) {
            toast(
                "warning",
                "Bluetooth unavailable.",
                "Mencetak lewat dialog browser.",
            );
            openPrintPage(transaction, formatAmount);
            return;
        }

        try {
            const lastPrinter = getLastPrinter();

            // Already connected or previously paired: always show spinner first.
            if (isConnected() || lastPrinter) {
                await showOverlay(
                    isConnected()
                        ? "Printing..."
                        : "Connecting to printer...",
                );
            }

            if (isConnected()) {
                await printTransaction(transaction, formatAmount);
                return;
            }

            if (lastPrinter) {
                try {
                    await reconnectPrinter(lastPrinter);
                    await holdOverlay(350);
                    toast("success", "Printer connected.", lastPrinter.name);
                    await showOverlay("Printing...");
                    await printTransaction(transaction, formatAmount);
                    return;
                } catch (error) {
                    clearOverlay();
                    console.warn(
                        "Auto-reconnect failed, opening printer modal.",
                        error,
                    );
                    toast(
                        "warning",
                        "Connection failed.",
                        "Silakan pilih atau pasangkan printer.",
                    );
                }
            }

            await beginPendingPrint(transaction, formatAmount);
        } catch (error) {
            clearOverlay();

            if (error?.name === "PrintCancelled") {
                return;
            }

            if (
                error?.name === "NotFoundError" ||
                error?.name === "SecurityError" ||
                error?.name === "BluetoothUnavailable"
            ) {
                throw error;
            }

            console.warn(
                "Bluetooth print failed, falling back to print page.",
                error,
            );

            openPrintPage(transaction, formatAmount);
        }
    }

    return {
        // UI bridge
        registerUi,
        getSavedPrinters,
        getConnectionState,
        getLastPrinter,

        // Printer manager
        pairPrinter,
        reconnectPrinter,
        connectPrinter,
        disconnectPrinter,
        removePrinter,

        // Modal actions used by Alpine
        pairAndPrint,
        connectSavedAndPrint,
        cancelPrinterModal,
        printFromModal,

        // Print API (backward compatible)
        printTransaction,
        printTransactionWithFallback,
        openPrintPage,
        buildReceiptData,
        buildReceiptHtml,
    };
})();
