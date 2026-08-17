/**
 * Bluetooth thermal printer helper (ESC/POS over Web Bluetooth).
 * Shared by Riwayat Transaksi and other POS pages.
 *
 * Architecture:
 * - Receipt builders: buildReceiptData / buildReceiptHtml
 * - Printer Manager: pair / silent reconnect / change printer / disconnect / storage
 * - Print orchestration: printTransaction / printTransactionWithFallback
 * - UI bridge: Alpine registers handlers for modal, name prompt, overlay, and toasts
 *
 * Lifecycle:
 * - Saved printers live in localStorage (deviceId + custom name + GATT UUIDs)
 * - On page load, autoReconnectOnLoad() uses navigator.bluetooth.getDevices()
 *   to reconnect WITHOUT opening the Bluetooth picker
 * - "Select Printer" / Pair New opens the picker only when the user asks to change
 * - Printing is Bluetooth/ESC/POS only (no window.print() fallback)
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
    const RECONNECT_WATCH_TIMEOUT_MS = 12000;
    const AUTO_RECONNECT_TIMEOUT_MS = 8000;

    let cachedDevice = null;
    let cachedCharacteristic = null;
    let cachedPrinterMeta = null;
    const disconnectBoundDevices = new WeakSet();
    let intentionalDisconnect = false;

    /** idle | connecting | connected | disconnected */
    let connectionStatus = "idle";
    let autoReconnectPromise = null;

    /** When silent reconnect fails, the next explicit "Select Printer" opens the picker. */
    let pendingReselectPrinterId = null;

    let uiHandlers = {
        openModal: null,
        closeModal: null,
        setOverlay: null,
        clearOverlay: null,
        refreshPrinters: null,
        promptPrinterName: null,
        onStatusChange: null,
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

    function resolveReceiptItems(transaction) {
        if (Array.isArray(transaction?.receipt_items)) {
            return transaction.receipt_items;
        }

        if (
            typeof ReceiptBuilder !== "undefined" &&
            typeof ReceiptBuilder.forReceipt === "function"
        ) {
            return ReceiptBuilder.forReceipt(transaction).items || [];
        }

        return transaction?.items || [];
    }

    function buildReceiptHtml(transaction, formatAmount = formatRupiah) {
        const itemsHtml = resolveReceiptItems(transaction)
            .map((item) => {
                return `
                <div class="print-item">
                    <div class="print-item-name">
                        ${item.product_name}
                    </div>

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

        // Items (product-level aggregation; no batch/expiration rows)
        resolveReceiptItems(transaction).forEach((item) => {
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

    function createPrinterId() {
        if (typeof crypto !== "undefined" && crypto.randomUUID) {
            return crypto.randomUUID();
        }

        return `printer-${Date.now()}`;
    }

    function normalizePrinterRecord(printer = {}) {
        const deviceName = printer.deviceName || printer.name || null;
        const name = printer.name || deviceName || "Bluetooth Printer";

        return {
            id: printer.id || createPrinterId(),
            name,
            deviceName,
            deviceId: printer.deviceId || null,
            serviceUuid: printer.serviceUuid || null,
            characteristicUuid: printer.characteristicUuid || null,
            lastUsedAt: printer.lastUsedAt || null,
        };
    }

    function readStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return { printers: [], lastPrinterId: null };
            }

            const parsed = JSON.parse(raw);
            const printers = (Array.isArray(parsed.printers) ? parsed.printers : [])
                .filter((printer) => printer && (printer.id || printer.deviceId))
                .map((printer) => normalizePrinterRecord(printer));

            return {
                printers,
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
                statusLabel:
                    connectedId === printer.id
                        ? "Connected"
                        : pendingReselectPrinterId === printer.id
                          ? "Select printer to continue"
                          : "Disconnected",
                needsReselect:
                    connectedId !== printer.id &&
                    pendingReselectPrinterId === printer.id,
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

    function findSavedPrinter(printerId) {
        if (!printerId) {
            return null;
        }

        return (
            readStorage().printers.find((item) => item.id === printerId) || null
        );
    }

    function savePrinter(printer, { preserveName = true } = {}) {
        const data = readStorage();
        const index = data.printers.findIndex(
            (item) =>
                (printer.id && item.id === printer.id) ||
                (printer.deviceId && item.deviceId === printer.deviceId),
        );

        const existing = index >= 0 ? data.printers[index] : null;
        const deviceName =
            printer.deviceName ||
            printer.bluetoothName ||
            existing?.deviceName ||
            null;

        let name;

        if (preserveName && existing?.name) {
            name = existing.name;
        } else if (printer.name) {
            name = printer.name;
        } else {
            name = deviceName || existing?.name || "Bluetooth Printer";
        }

        const record = normalizePrinterRecord({
            id: existing?.id || printer.id || createPrinterId(),
            name,
            deviceName,
            deviceId: printer.deviceId || existing?.deviceId || null,
            serviceUuid:
                printer.serviceUuid ?? existing?.serviceUuid ?? null,
            characteristicUuid:
                printer.characteristicUuid ??
                existing?.characteristicUuid ??
                null,
            lastUsedAt: new Date().toISOString(),
        });

        if (index >= 0) {
            data.printers[index] = {
                ...existing,
                ...record,
                id: existing.id,
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

    function renamePrinter(printerId, customName) {
        const trimmed = String(customName || "").trim();

        if (!trimmed) {
            throw new Error("Nama printer tidak boleh kosong.");
        }

        const data = readStorage();
        const index = data.printers.findIndex((item) => item.id === printerId);

        if (index < 0) {
            throw new Error("Printer not found.");
        }

        data.printers[index] = {
            ...data.printers[index],
            name: trimmed,
            lastUsedAt: new Date().toISOString(),
        };
        data.lastPrinterId = printerId;
        writeStorage(data);
        cachedPrinterMeta = data.printers[index];

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

        if (pendingReselectPrinterId === printerId) {
            pendingReselectPrinterId = null;
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

    function setConnectionStatus(status) {
        connectionStatus = status;
        refreshPrinterList();

        if (typeof uiHandlers.onStatusChange === "function") {
            uiHandlers.onStatusChange(getConnectionState());
        }
    }

    function getConnectionState() {
        const selected = cachedPrinterMeta || getLastPrinter();
        const connected = isConnected();

        return {
            connected,
            status: connected ? "connected" : connectionStatus,
            printer: selected,
            deviceName: cachedDevice?.name || selected?.name || null,
            pendingReselectPrinterId,
            canAutoReconnect: canAttemptSilentReconnect(),
            hasSavedPrinter: Boolean(getLastPrinter()),
        };
    }

    function refreshPrinterStatus() {
        if (!cachedDevice?.gatt?.connected) {
            cachedCharacteristic = null;

            if (connectionStatus === "connected") {
                connectionStatus = getLastPrinter() ? "disconnected" : "idle";
            }
        } else if (cachedCharacteristic) {
            connectionStatus = "connected";
        }

        refreshPrinterList();

        if (typeof uiHandlers.onStatusChange === "function") {
            uiHandlers.onStatusChange(getConnectionState());
        }

        return getConnectionState();
    }

    function makeError(name, message) {
        const error = new Error(message);
        error.name = name;
        return error;
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

        throw makeError(
            "PrinterCharacteristicMissing",
            "Karakteristik printer tidak ditemukan.",
        );
    }

    function bindDeviceDisconnect(device) {
        if (disconnectBoundDevices.has(device)) {
            return;
        }

        disconnectBoundDevices.add(device);

        device.addEventListener("gattserverdisconnected", () => {
            if (cachedDevice && cachedDevice.id === device.id) {
                cachedCharacteristic = null;
            }

            // Keep saved printer metadata — only the live GATT link was lost.
            if (!intentionalDisconnect) {
                setConnectionStatus(getLastPrinter() ? "disconnected" : "idle");
                toast(
                    "warning",
                    "Printer disconnected.",
                    "Nyalakan printer lalu klik Reconnect.",
                );
            } else {
                refreshPrinterList();
            }
        });
    }

    /**
     * Open GATT on an already-authorized BluetoothDevice.
     * Prefer watching advertisements when available (Chrome reconnect path).
     */
    async function openGattConnection(device, { timeoutMs = RECONNECT_WATCH_TIMEOUT_MS } = {}) {
        if (!device?.gatt) {
            throw makeError("PrinterNotFound", "Printer device tidak valid.");
        }

        if (device.gatt.connected) {
            return device.gatt;
        }

        // Fast path: some stacks still allow connect() without advertisement scan.
        try {
            return await device.gatt.connect();
        } catch (directError) {
            console.warn(
                "[ThermalPrinter] Direct GATT connect failed, trying watchAdvertisements.",
                directError,
            );
        }

        if (typeof device.watchAdvertisements !== "function") {
            throw makeError(
                "PrinterNeedsReselect",
                "Browser tidak dapat reconnect otomatis. Pilih printer lagi.",
            );
        }

        const abortController = new AbortController();

        try {
            await new Promise((resolve, reject) => {
                let settled = false;

                const cleanup = () => {
                    clearTimeout(timer);
                    device.removeEventListener(
                        "advertisementreceived",
                        onAdvertisement,
                    );
                };

                const timer = setTimeout(() => {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    cleanup();
                    abortController.abort();
                    reject(
                        makeError(
                            "PrinterTimeout",
                            "Printer tidak merespons. Pastikan printer menyala dan dekat.",
                        ),
                    );
                }, timeoutMs);

                const onAdvertisement = () => {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    cleanup();
                    abortController.abort();
                    resolve();
                };

                device.addEventListener("advertisementreceived", onAdvertisement);

                device
                    .watchAdvertisements({ signal: abortController.signal })
                    .catch((error) => {
                        if (settled || error?.name === "AbortError") {
                            return;
                        }

                        settled = true;
                        cleanup();
                        reject(error);
                    });
            });
        } catch (error) {
            if (
                error?.name === "PrinterTimeout" ||
                error?.name === "PrinterNeedsReselect"
            ) {
                throw error;
            }

            throw makeError(
                "PrinterNeedsReselect",
                error?.message ||
                    "Gagal mencari printer. Pilih printer lagi.",
            );
        }

        try {
            return await device.gatt.connect();
        } catch (error) {
            throw makeError(
                "ConnectionFailed",
                error?.message || "Gagal membuka koneksi printer.",
            );
        }
    }

    async function connectToDevice(device, savedPrinter = null, options = {}) {
        const {
            preserveName = Boolean(savedPrinter?.name),
            timeoutMs = RECONNECT_WATCH_TIMEOUT_MS,
        } = options;

        cachedDevice = device;
        bindDeviceDisconnect(device);
        setConnectionStatus("connecting");

        const server = await openGattConnection(device, { timeoutMs });

        if (!server?.connected && !device.gatt?.connected) {
            setConnectionStatus("disconnected");
            throw makeError("ConnectionFailed", "Koneksi printer gagal.");
        }

        const found = await findWritableCharacteristic(device.gatt, {
            serviceUuid: savedPrinter?.serviceUuid,
            characteristicUuid: savedPrinter?.characteristicUuid,
        });

        cachedCharacteristic = found.characteristic;

        if (!isConnected()) {
            cachedCharacteristic = null;
            setConnectionStatus("disconnected");
            throw makeError(
                "ConnectionFailed",
                "Printer terhubung tetapi belum siap mencetak.",
            );
        }

        const meta = savePrinter(
            {
                id: savedPrinter?.id,
                name: savedPrinter?.name,
                deviceName: device.name || savedPrinter?.deviceName || null,
                deviceId: device.id,
                serviceUuid: found.serviceUuid,
                characteristicUuid: found.characteristicUuid,
            },
            { preserveName },
        );

        if (pendingReselectPrinterId === meta.id) {
            pendingReselectPrinterId = null;
        }

        setConnectionStatus("connected");

        return {
            characteristic: cachedCharacteristic,
            printer: meta,
        };
    }

    /**
     * Page-load / silent reconnect using previously authorized devices only.
     * Never opens the Bluetooth picker (no user gesture available on load).
     */
    async function autoReconnectOnLoad() {
        if (autoReconnectPromise) {
            return autoReconnectPromise;
        }

        autoReconnectPromise = (async () => {
            if (!navigator.bluetooth) {
                setConnectionStatus(getLastPrinter() ? "disconnected" : "idle");
                return { ok: false, reason: "no-bluetooth" };
            }

            if (isConnected()) {
                setConnectionStatus("connected");
                return { ok: true, already: true };
            }

            const printer = getLastPrinter();

            if (!printer?.deviceId) {
                setConnectionStatus("idle");
                return { ok: false, reason: "none" };
            }

            // Remember which printer is selected even while disconnected.
            cachedPrinterMeta = printer;

            if (!canAttemptSilentReconnect()) {
                setConnectionStatus("disconnected");
                console.info(
                    "[ThermalPrinter] getDevices() unavailable — auto-reconnect needs Chrome Bluetooth permissions backend / experimental flag.",
                );
                return { ok: false, reason: "getDevices-unavailable" };
            }

            setConnectionStatus("connecting");

            try {
                const device = await findGrantedDevice(printer.deviceId);

                if (!device) {
                    setConnectionStatus("disconnected");
                    return { ok: false, reason: "not-authorized" };
                }

                await connectToDevice(device, printer, {
                    preserveName: true,
                    timeoutMs: AUTO_RECONNECT_TIMEOUT_MS,
                });

                setConnectionStatus("connected");
                return { ok: true, printer: cachedPrinterMeta };
            } catch (error) {
                console.warn(
                    "[ThermalPrinter] Auto-reconnect failed (printer may be off).",
                    error,
                );
                setConnectionStatus("disconnected");
                // Keep localStorage + cachedPrinterMeta intact.
                cachedPrinterMeta = printer;
                return { ok: false, reason: "offline", error };
            }
        })();

        try {
            return await autoReconnectPromise;
        } finally {
            autoReconnectPromise = null;
        }
    }

    async function findGrantedDevice(deviceId) {
        if (!deviceId || typeof navigator.bluetooth?.getDevices !== "function") {
            return null;
        }

        try {
            const devices = await navigator.bluetooth.getDevices();
            return devices.find((device) => device.id === deviceId) || null;
        } catch (error) {
            console.warn("[ThermalPrinter] getDevices() failed.", error);
            return null;
        }
    }

    async function requestBluetoothDevice() {
        return navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: OPTIONAL_SERVICES,
        });
    }

    async function promptForPrinterName(device, fallbackName = null) {
        const deviceName = device?.name || fallbackName || "Bluetooth Printer";

        if (typeof uiHandlers.promptPrinterName !== "function") {
            return deviceName;
        }

        try {
            const customName = await uiHandlers.promptPrinterName({
                deviceName,
                defaultName: fallbackName || deviceName,
            });

            const trimmed = String(customName || "").trim();
            return trimmed || deviceName;
        } catch {
            return deviceName;
        }
    }

    async function pairPrinter({ askForName = true } = {}) {
        assertBluetoothAvailable();

        // requestDevice must run under the user gesture — before overlays/awaits.
        const device = await requestBluetoothDevice();

        await showOverlay("Connecting to printer...");

        try {
            const result = await connectToDevice(device, null, {
                preserveName: false,
            });

            let printer = result.printer;

            if (askForName) {
                clearOverlay();
                const customName = await promptForPrinterName(
                    device,
                    printer.name,
                );
                printer = renamePrinter(printer.id, customName);
            }

            await holdOverlay(200);
            toast("success", "Printer connected.", printer.name);
            return printer;
        } finally {
            clearOverlay();
        }
    }

    /**
     * Silent reconnect using a previously authorized BluetoothDevice.
     * Does not open the browser picker.
     */
    async function reconnectPrinter(savedPrinter = null) {
        assertBluetoothAvailable();

        const printer = savedPrinter || getLastPrinter();

        if (!printer?.deviceId) {
            throw makeError("PrinterNotFound", "Printer not found.");
        }

        if (
            cachedDevice?.id === printer.deviceId &&
            cachedDevice?.gatt?.connected &&
            cachedCharacteristic
        ) {
            touchPrinter(printer.id);
            cachedPrinterMeta = printer;
            refreshPrinterList();
            return printer;
        }

        let device = null;

        if (cachedDevice?.id === printer.deviceId) {
            device = cachedDevice;
        } else {
            device = await findGrantedDevice(printer.deviceId);
        }

        if (!device) {
            throw makeError(
                "PrinterNeedsReselect",
                "Printer tersimpan tidak tersedia. Pilih printer lagi.",
            );
        }

        const result = await connectToDevice(device, printer, {
            preserveName: true,
        });

        return result.printer;
    }

    /**
     * Re-authorize a saved printer via the browser picker and update the
     * existing history entry (no duplicate records).
     */
    async function reselectAndConnectPrinter(savedPrinter) {
        assertBluetoothAvailable();

        if (!savedPrinter?.id) {
            throw makeError("PrinterNotFound", "Printer not found.");
        }

        // Keep requestDevice first to preserve the user gesture.
        const device = await requestBluetoothDevice();

        await showOverlay("Connecting to printer...");

        try {
            // If another saved entry already owns this deviceId, merge into that one
            // but prefer updating the entry the user clicked when ids differ only by stale deviceId.
            const existingByDevice = readStorage().printers.find(
                (item) => item.deviceId === device.id && item.id !== savedPrinter.id,
            );

            const target = existingByDevice
                ? {
                      ...existingByDevice,
                      name: savedPrinter.name || existingByDevice.name,
                  }
                : savedPrinter;

            if (existingByDevice) {
                // Drop the stale duplicate the user tried to reconnect.
                const data = readStorage();
                data.printers = data.printers.filter(
                    (item) => item.id !== savedPrinter.id,
                );
                writeStorage(data);
            }

            const result = await connectToDevice(device, target, {
                preserveName: true,
            });

            await holdOverlay(350);
            toast("success", "Printer connected.", result.printer.name);
            return result.printer;
        } finally {
            clearOverlay();
        }
    }

    function canAttemptSilentReconnect() {
        return typeof navigator.bluetooth?.getDevices === "function";
    }

    /**
     * Connect to a saved printer and make it active for printing.
     * By default does NOT open the Bluetooth picker (POS reconnect behavior).
     * Pass forceReselect / allowPickerFallback to change or recover a device.
     */
    async function connectPrinter(printerId = null, options = {}) {
        assertBluetoothAvailable();

        const {
            forceReselect = false,
            allowPickerFallback = false,
        } = options;
        const printers = readStorage().printers;
        const printer = printerId
            ? printers.find((item) => item.id === printerId)
            : getLastPrinter();

        if (!printer) {
            throw makeError("PrinterNotFound", "Printer not found.");
        }

        // Already on this printer — keep it active and print with it.
        if (
            cachedDevice?.id === printer.deviceId &&
            isConnected() &&
            cachedPrinterMeta?.id === printer.id
        ) {
            touchPrinter(printer.id);
            pendingReselectPrinterId = null;
            setConnectionStatus("connected");
            return printer;
        }

        // User explicitly asked to pick / change the device.
        if (forceReselect || pendingReselectPrinterId === printer.id) {
            pendingReselectPrinterId = null;
            refreshPrinterList();

            try {
                setConnectionStatus("connecting");
                return await reselectAndConnectPrinter(printer);
            } catch (error) {
                setConnectionStatus("disconnected");

                if (
                    error?.name === "NotFoundError" ||
                    error?.name === "SecurityError"
                ) {
                    toast("info", "Pemilihan printer dibatalkan.");
                    throw error;
                }

                toast(
                    "error",
                    "Connection failed.",
                    error?.message || "Tidak dapat terhubung ke printer.",
                );
                throw error;
            }
        }

        const hasCachedMatch = cachedDevice?.id === printer.deviceId;
        const canUseGrantedDevices = canAttemptSilentReconnect();

        // Silent reconnect via cache or getDevices() — no picker.
        if (hasCachedMatch || canUseGrantedDevices) {
            await showOverlay("Connecting to printer...");
            setConnectionStatus("connecting");

            try {
                const connected = await reconnectPrinter(printer);
                pendingReselectPrinterId = null;
                setConnectionStatus("connected");
                await holdOverlay(350);
                toast("success", "Printer connected.", connected.name);
                return connected;
            } catch (error) {
                clearOverlay();
                setConnectionStatus("disconnected");
                cachedPrinterMeta = printer;

                if (allowPickerFallback) {
                    console.warn(
                        "[ThermalPrinter] Silent reconnect failed, opening device picker.",
                        error,
                    );
                    try {
                        setConnectionStatus("connecting");
                        return await reselectAndConnectPrinter(printer);
                    } catch (pickerError) {
                        setConnectionStatus("disconnected");

                        if (
                            pickerError?.name === "NotFoundError" ||
                            pickerError?.name === "SecurityError"
                        ) {
                            toast("info", "Pemilihan printer dibatalkan.");
                            throw pickerError;
                        }

                        toast(
                            "error",
                            "Connection failed.",
                            pickerError?.message ||
                                "Tidak dapat terhubung ke printer.",
                        );
                        throw pickerError;
                    }
                }

                pendingReselectPrinterId = printer.id;
                refreshPrinterList();

                const message =
                    error?.name === "PrinterTimeout"
                        ? "Printer tidak merespons. Nyalakan printer lalu Reconnect, atau Select Printer untuk ganti perangkat."
                        : "Tidak dapat terhubung. Klik Reconnect setelah printer menyala, atau Select Printer untuk ganti.";

                toast("warning", "Printer disconnected.", message);

                const wrapped = makeError("PrinterNeedsReselect", message);
                wrapped.cause = error;
                throw wrapped;
            } finally {
                clearOverlay();
            }
        }

        // No getDevices / no cached handle: cannot silent-reconnect.
        setConnectionStatus("disconnected");
        cachedPrinterMeta = printer;
        pendingReselectPrinterId = printer.id;
        refreshPrinterList();

        if (allowPickerFallback) {
            try {
                setConnectionStatus("connecting");
                return await reselectAndConnectPrinter(printer);
            } catch (error) {
                setConnectionStatus("disconnected");

                if (
                    error?.name === "NotFoundError" ||
                    error?.name === "SecurityError"
                ) {
                    toast("info", "Pemilihan printer dibatalkan.");
                    throw error;
                }

                toast(
                    "error",
                    "Connection failed.",
                    error?.message || "Tidak dapat terhubung ke printer.",
                );
                throw error;
            }
        }

        const message =
            "Browser tidak bisa reconnect otomatis. Klik Select Printer untuk memilih perangkat.";
        toast("warning", "Printer disconnected.", message);
        throw makeError("PrinterNeedsReselect", message);
    }

    async function disconnectPrinter({ silent = false } = {}) {
        intentionalDisconnect = true;

        try {
            if (cachedDevice?.gatt?.connected) {
                cachedDevice.gatt.disconnect();
            }
        } catch {
            // Ignore disconnect errors.
        } finally {
            intentionalDisconnect = false;
        }

        cachedCharacteristic = null;
        cachedDevice = null;
        // Keep last selected printer identity for status / reconnect.
        cachedPrinterMeta = getLastPrinter();
        setConnectionStatus(cachedPrinterMeta ? "disconnected" : "idle");

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
            // Silent only — requestDevice requires a user gesture.
            await reconnectPrinter(lastPrinter);
            return cachedCharacteristic;
        }

        throw makeError("PrinterNotFound", "Printer not found.");
    }

    async function sendPrintData(data) {
        const characteristic = await ensureCharacteristic();

        if (!characteristic || !isConnected()) {
            throw makeError(
                "ConnectionFailed",
                "Printer tidak terhubung. Klik Connect lalu coba lagi.",
            );
        }

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

        try {
            await printTransaction(transaction, formatAmount);
            closeModal();
            clearPendingPrint();
        } catch (error) {
            // Keep the pending receipt so the user can retry from the modal.
            // Never fall back to the browser HTML print dialog.
            openModal();
            refreshPrinterList();
            toast(
                "error",
                "Print failed.",
                error?.message || "Gagal mencetak ke printer Bluetooth.",
            );
            throw error;
        }
    }

    async function pairAndPrint() {
        try {
            await pairPrinter({ askForName: true });
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

            if (pendingPrint) {
                openModal();
                refreshPrinterList();
            }

            toast(
                "error",
                "Connection failed.",
                error?.message || "Gagal memasangkan printer.",
            );
            throw error;
        }
    }

    /** Reconnect saved printer (no picker) then print pending receipt if any. */
    async function connectSavedAndPrint(printerId) {
        try {
            await connectPrinter(printerId, { allowPickerFallback: false });
            await printPendingIfAny();
        } catch (error) {
            if (
                error?.name === "NotFoundError" ||
                error?.name === "SecurityError"
            ) {
                toast("info", "Pemilihan printer dibatalkan.");
                return;
            }

            if (error?.name === "PrinterNotFound") {
                toast("error", "Printer not found.");
            }

            if (pendingPrint) {
                openModal();
                refreshPrinterList();
            }

            throw error;
        }
    }

    /**
     * Explicitly change/re-authorize a printer via the Bluetooth picker,
     * then print any pending receipt.
     */
    async function selectPrinterAndPrint(printerId = null) {
        try {
            if (printerId) {
                await connectPrinter(printerId, {
                    forceReselect: true,
                    allowPickerFallback: true,
                });
            } else {
                await pairPrinter({ askForName: true });
            }

            await printPendingIfAny();
        } catch (error) {
            clearOverlay();

            if (
                error?.name === "NotFoundError" ||
                error?.name === "SecurityError"
            ) {
                toast("info", "Pemilihan printer dibatalkan.");
                return;
            }

            if (pendingPrint) {
                openModal();
                refreshPrinterList();
            }

            toast(
                "error",
                "Connection failed.",
                error?.message || "Gagal memilih printer.",
            );
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
            if (
                error?.name === "PrintCancelled" ||
                error?.name === "NotFoundError" ||
                error?.name === "SecurityError" ||
                error?.name === "PrinterNeedsReselect"
            ) {
                return;
            }

            toast(
                "error",
                "Connection failed.",
                error?.message || "Gagal mencetak.",
            );
        }
    }

    /**
     * Print via Bluetooth thermal printer only.
     * Prefers an already-connected session or silent getDevices() reconnect.
     * Opens the printer modal only when reconnect is impossible — never
     * auto-opens the Bluetooth picker, and never uses window.print().
     */
    async function printTransactionWithFallback(transaction, formatAmount) {
        if (!navigator.bluetooth) {
            toast(
                "error",
                "Bluetooth unavailable.",
                "Browser ini tidak mendukung Web Bluetooth. Gunakan Chrome/Edge.",
            );
            throw makeError(
                "BluetoothUnavailable",
                "Browser tidak mendukung Web Bluetooth.",
            );
        }

        try {
            if (autoReconnectPromise) {
                await autoReconnectPromise;
            }

            if (isConnected()) {
                await printTransaction(transaction, formatAmount);
                return;
            }

            const lastPrinter = getLastPrinter();

            if (lastPrinter) {
                try {
                    await connectPrinter(lastPrinter.id, {
                        allowPickerFallback: false,
                    });
                    await printTransaction(transaction, formatAmount);
                    return;
                } catch (error) {
                    clearOverlay();
                    console.warn(
                        "[ThermalPrinter] Silent reconnect before print failed, opening printer modal.",
                        error,
                    );

                    toast(
                        "warning",
                        "Printer disconnected.",
                        "Nyalakan printer lalu Reconnect, atau Select Printer untuk ganti.",
                    );

                    await beginPendingPrint(transaction, formatAmount);
                    return;
                }
            }

            await beginPendingPrint(transaction, formatAmount);
        } catch (error) {
            clearOverlay();

            if (error?.name === "PrintCancelled") {
                return;
            }

            if (error?.name === "BluetoothUnavailable") {
                throw error;
            }

            console.error("[ThermalPrinter] Print failed.", error);

            toast(
                "error",
                "Print failed.",
                error?.message ||
                    "Gagal mencetak ke printer Bluetooth. Coba Reconnect / Select Printer.",
            );

            if (pendingPrint) {
                openModal();
                refreshPrinterList();
                return;
            }

            throw error;
        }
    }

    return {
        // UI bridge
        registerUi,
        getSavedPrinters,
        getConnectionState,
        getLastPrinter,
        refreshPrinterStatus,
        autoReconnectOnLoad,

        // Printer manager
        pairPrinter,
        reconnectPrinter,
        connectPrinter,
        disconnectPrinter,
        removePrinter,
        renamePrinter,
        savePrinter,
        reselectAndConnectPrinter,

        // Modal actions used by Alpine
        pairAndPrint,
        connectSavedAndPrint,
        selectPrinterAndPrint,
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
