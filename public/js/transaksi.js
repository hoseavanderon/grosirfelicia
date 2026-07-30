function transaksiPage() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const paymentOptions = [
        { value: "cash", label: "Sudah Bayar - Cash" },
        { value: "tf", label: "Sudah Bayar - TF" },
    ];

    return {
        calendarOpen: false,
        copied: false,
        loading: false,
        detailModalOpen: false,
        selectedTransaction: null,
        paymentMenuOpen: null,
        paymentUpdating: null,
        depositingDate: null,
        confirmModalOpen: false,
        confirmMessage: "",
        confirmAction: null,

        editMode: false,
        editForm: null,
        productOptions: [],
        savingEdit: false,
        deletingTransaction: false,
        editCustomerDropdownOpen: false,
        editCustomerResults: [],
        editCustomerSearchLoading: false,
        editCustomerSearchTimer: null,
        editCustomerSearchAbort: null,

        transactions: [],
        transactionCount: 0,
        totalAmount: 0,
        paymentOptions,

        fromDate: new Date(today),
        toDate: new Date(today),
        displayMonth: new Date(today),
        weekdays: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],

        init() {
            this.fetchTransactions();
        },

        get fromLabel() {
            return this.formatShortDate(this.fromDate);
        },

        get toLabel() {
            return this.formatShortDate(this.toDate);
        },

        get formattedRange() {
            if (!this.fromDate) {
                return "Pilih tanggal";
            }

            if (this.toDate && !this.isSameDate(this.fromDate, this.toDate)) {
                return `${this.fromLabel} - ${this.toLabel}`;
            }

            return this.fromLabel;
        },

        get summaryTitle() {
            if (
                this.fromDate &&
                this.toDate &&
                this.isSameDate(this.fromDate, this.toDate) &&
                this.isSameDate(this.fromDate, today)
            ) {
                return "Hari Ini";
            }

            return this.formattedRange;
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

        get groupedTransactions() {
            const groups = {};

            this.transactions.forEach((transaction) => {
                const dateKey = (transaction.datetime || "").split(" ")[0];

                if (!dateKey) {
                    return;
                }

                if (!groups[dateKey]) {
                    groups[dateKey] = [];
                }

                groups[dateKey].push(transaction);
            });

            return Object.keys(groups)
                .sort()
                .map((dateKey) => ({
                    dateKey,
                    dateLabel: this.formatGroupDate(dateKey),
                    transactions: groups[dateKey],
                }));
        },

        formatShortDate(date) {
            return date.toLocaleDateString("id-ID", {
                day: "numeric",
                month: "long",
            });
        },

        formatGroupDate(dateKey) {
            const [year, month, day] = dateKey.split("-").map(Number);

            return new Date(year, month - 1, day).toLocaleDateString("id-ID", {
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        },

        formatRupiah(value) {
            return new Intl.NumberFormat("id-ID").format(value || 0);
        },

        csrfToken() {
            return (
                document.querySelector('meta[name="csrf-token"]')?.content || ""
            );
        },

        openConfirm(message, action) {
            this.confirmMessage = message;
            this.confirmAction = action;
            this.confirmModalOpen = true;
        },

        cancelConfirm() {
            this.confirmModalOpen = false;
            this.confirmMessage = "";
            this.confirmAction = null;
        },

        async executeConfirm() {
            const action = this.confirmAction;
            this.cancelConfirm();

            if (typeof action === "function") {
                await action();
            }
        },

        canDepositGroup(group) {
            return group.transactions.some((transaction) =>
                ["cash", "tf"].includes(transaction.metode_pembayaran),
            );
        },

        isPaymentEditable(transaction) {
            return transaction.metode_pembayaran !== "setor_bos";
        },

        async fetchTransactionsForRange(fromDate, toDate) {
            const params = new URLSearchParams({
                from: this.formatApiDate(fromDate),
                to: this.formatApiDate(toDate),
            });

            const response = await fetch(
                `/transactions/list?${params.toString()}`,
                {
                    headers: {
                        Accept: "application/json",
                    },
                },
            );

            if (!response.ok) {
                throw new Error("Failed to load transactions");
            }

            const data = await response.json();

            return data.transactions || [];
        },

        async fetchTransactions() {
            this.loading = true;
            this.paymentMenuOpen = null;

            try {
                let url = "/transactions/list";

                if (this.fromDate && this.toDate) {
                    const params = new URLSearchParams({
                        from: this.formatApiDate(this.fromDate),
                        to: this.formatApiDate(this.toDate),
                    });

                    url += "?" + params.toString();
                }

                const response = await fetch(url, {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load transactions");
                }

                const data = await response.json();

                this.transactions = data.transactions;
                this.transactionCount = data.transaction_count;
                this.totalAmount = data.total_amount;
            } catch (error) {
                console.error(error);

                this.transactions = [];
                this.transactionCount = 0;
                this.totalAmount = 0;
            } finally {
                this.loading = false;
            }
        },

        formatApiDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },

        getOutstandingTransactions() {
            return this.transactions.filter(
                (transaction) =>
                    transaction.metode_pembayaran === "belum_bayar",
            );
        },

        get editTotal() {
            if (!this.editForm?.items) {
                return 0;
            }

            return this.editForm.items.reduce(
                (total, item) => total + (item.line_total || 0),
                0,
            );
        },

        showToast(type, title, message) {
            window.dispatchEvent(
                new CustomEvent("toast", {
                    detail: { type, title, message },
                }),
            );
        },

        buildCopyText(transactions) {
            const outstanding = transactions.filter(
                (transaction) =>
                    transaction.metode_pembayaran === "belum_bayar",
            );

            const groups = {};

            outstanding.forEach((transaction) => {
                const dateKey = (transaction.datetime || "").split(" ")[0];

                if (!dateKey) {
                    return;
                }

                if (!groups[dateKey]) {
                    groups[dateKey] = [];
                }

                groups[dateKey].push(transaction);
            });

            const result = [];

            Object.keys(groups)
                .sort()
                .forEach((dateKey) => {
                    const list = groups[dateKey];

                    const total = list.reduce(
                        (sum, item) => sum + (item.amount || 0),
                        0,
                    );

                    const [year, month, day] = dateKey.split("-");

                    result.push(
                        `${parseInt(day)}/${parseInt(month)}/${year.slice(-2)} ( ${list.length} nota => ${this.formatRupiah(total)} )`,
                    );

                    list.forEach((transaction, index) => {
                        result.push(
                            `${index + 1}. ${transaction.customer} ( ${this.formatRupiah(transaction.amount)} )`,
                        );
                    });

                    result.push("");
                });

            return result.join("\n");
        },

        async copySummary() {
            try {
                const outstanding = this.getOutstandingTransactions();

                if (outstanding.length === 0) {
                    this.showToast(
                        "info",
                        "Info",
                        "Saat ini tidak ada tagihan yang belum dibayar.",
                    );
                    return;
                }

                const text = this.buildCopyText(this.transactions);

                if (!text.trim()) {
                    this.showToast(
                        "info",
                        "Info",
                        "Saat ini tidak ada tagihan yang belum dibayar.",
                    );
                    return;
                }

                await navigator.clipboard.writeText(text);

                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 1500);
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    "Gagal menyalin laporan.",
                );
            }
        },

        isSameDate(a, b) {
            if (!a || !b) {
                return false;
            }

            return a.toDateString() === b.toDateString();
        },

        isInRange(day) {
            if (!day || !this.fromDate || !this.toDate) {
                return false;
            }

            const start = Math.min(
                this.fromDate.getTime(),
                this.toDate.getTime(),
            );
            const end = Math.max(
                this.fromDate.getTime(),
                this.toDate.getTime(),
            );
            const time = day.getTime();

            return time > start && time < end;
        },

        dayClass(day) {
            if (!day) {
                return "calendar-day calendar-day-empty";
            }

            if (
                this.isSameDate(day, this.fromDate) ||
                this.isSameDate(day, this.toDate)
            ) {
                return "calendar-day calendar-day-selected";
            }

            if (this.isInRange(day)) {
                return "calendar-day calendar-day-in-range";
            }

            return "calendar-day calendar-day-default";
        },

        selectDay(day) {
            if (!day) {
                return;
            }

            if (!this.fromDate || (this.fromDate && this.toDate)) {
                this.fromDate = new Date(day);
                this.toDate = null;
                this.displayMonth = new Date(
                    day.getFullYear(),
                    day.getMonth(),
                    1,
                );
                return;
            }

            if (this.isSameDate(day, this.fromDate)) {
                this.toDate = new Date(day);
                this.fetchTransactions();
                this.calendarOpen = false;
                return;
            }

            if (day.getTime() < this.fromDate.getTime()) {
                this.toDate = this.fromDate;
                this.fromDate = new Date(day);
            } else {
                this.toDate = new Date(day);
            }

            this.fetchTransactions();
            this.calendarOpen = false;
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
            this.fromDate = new Date(today);
            this.toDate = new Date(today);
            this.displayMonth = new Date(today);
            this.fetchTransactions();
            this.calendarOpen = false;
        },

        applyDate() {
            if (!this.toDate) {
                this.toDate = new Date(this.fromDate);
            }

            this.fetchTransactions();
            this.calendarOpen = false;
        },

        openDetail(transaction) {
            this.paymentMenuOpen = null;
            this.exitEditMode();
            this.selectedTransaction = transaction;
            this.detailModalOpen = true;
        },

        closeDetail() {
            this.exitEditMode();
            this.detailModalOpen = false;
            this.selectedTransaction = null;
        },

        togglePaymentMenu(transactionId) {
            const transaction = this.transactions.find(
                (item) => item.id === transactionId,
            );

            if (transaction && !this.isPaymentEditable(transaction)) {
                return;
            }

            this.paymentMenuOpen =
                this.paymentMenuOpen === transactionId ? null : transactionId;
        },

        closePaymentMenu() {
            this.paymentMenuOpen = null;
        },

        applyPaymentUpdate(transaction, payload) {
            transaction.metode_pembayaran = payload.metode_pembayaran;
            transaction.status = payload.status;
            transaction.status_label = payload.status_label;
            transaction.status_class = payload.status_class;

            if (
                Number(this.selectedTransaction?.id) === Number(transaction.id)
            ) {
                this.selectedTransaction.metode_pembayaran =
                    payload.metode_pembayaran;
                this.selectedTransaction.status = payload.status;
                this.selectedTransaction.status_label = payload.status_label;
                this.selectedTransaction.status_class = payload.status_class;
            }
        },

        applyBulkPaymentUpdates(updates) {
            const updateMap = new Map(
                updates.map((payload) => [Number(payload.id), payload]),
            );

            this.transactions = this.transactions.map((transaction) => {
                const payload = updateMap.get(Number(transaction.id));

                if (!payload) {
                    return transaction;
                }

                return {
                    ...transaction,
                    metode_pembayaran: payload.metode_pembayaran,
                    status: payload.status,
                    status_label: payload.status_label,
                    status_class: payload.status_class,
                };
            });

            if (this.selectedTransaction) {
                const payload = updateMap.get(
                    Number(this.selectedTransaction.id),
                );

                if (payload) {
                    this.applyPaymentUpdate(this.selectedTransaction, payload);
                }
            }
        },

        requestPaymentUpdate(transaction, metode) {
            if (
                !this.isPaymentEditable(transaction) ||
                transaction.metode_pembayaran === metode
            ) {
                this.paymentMenuOpen = null;
                return;
            }

            this.paymentMenuOpen = null;

            this.openConfirm(
                "Apakah anda yakin ingin mengubah status pembayaran?",
                () => this.updatePayment(transaction, metode),
            );
        },

        async updatePayment(transaction, metode) {
            if (this.paymentUpdating === transaction.id) {
                return;
            }

            this.paymentUpdating = transaction.id;

            try {
                const response = await fetch(
                    `/transactions/${transaction.id}/payment`,
                    {
                        method: "PATCH",
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": this.csrfToken(),
                        },
                        body: JSON.stringify({
                            metode_pembayaran: metode,
                        }),
                    },
                );

                if (!response.ok) {
                    throw new Error("Failed to update payment");
                }

                const data = await response.json();
                this.applyPaymentUpdate(transaction, data.transaction);
            } catch (error) {
                console.error(error);
            } finally {
                this.paymentUpdating = null;
            }
        },

        requestDepositByDate(dateKey) {
            if (this.depositingDate === dateKey) {
                return;
            }

            this.openConfirm(
                "Apakah anda yakin semua transaksi untuk tanggal ini sudah disetor?",
                () => this.depositByDate(dateKey),
            );
        },

        async depositByDate(dateKey) {
            if (this.depositingDate === dateKey) {
                return;
            }

            this.depositingDate = dateKey;

            try {
                const response = await fetch("/transactions/deposit-by-date", {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify({ date: dateKey }),
                });

                if (!response.ok) {
                    throw new Error("Failed to deposit transactions");
                }

                const data = await response.json();
                const updates = data.transactions || [];

                if (updates.length === 0) {
                    this.showToast(
                        "error",
                        "Gagal",
                        "Tidak ada transaksi cash/transfer yang perlu disetor untuk tanggal ini.",
                    );
                    return;
                }

                this.applyBulkPaymentUpdates(updates);

                this.showToast(
                    "success",
                    "Berhasil",
                    `${updates.length} transaksi berhasil ditandai sudah setor.`,
                );
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    error.message || "Gagal memproses setor untuk tanggal ini.",
                );
            } finally {
                this.depositingDate = null;
            }
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

        async printReceipt() {
            if (!this.selectedTransaction) {
                return;
            }

            try {
                await ThermalPrinter.printTransactionWithFallback(
                    this.selectedTransaction,
                    this.formatRupiah.bind(this),
                );
            } catch (error) {
                console.error(error);
                alert(error?.message || "Gagal mencetak struk.");
            }
        },

        async loadProductOptions() {
            if (!this.selectedTransaction) {
                return;
            }

            const params = new URLSearchParams({
                transaction_id: String(this.selectedTransaction.id),
            });

            const response = await fetch(
                `/transactions/detail-products?${params.toString()}`,
                {
                    headers: {
                        Accept: "application/json",
                    },
                },
            );

            if (!response.ok) {
                throw new Error("Failed to load products");
            }

            const data = await response.json();
            this.productOptions = data.products || [];
        },

        createEditItemFromTransactionItem(item) {
            return {
                ...this.createEditItemComboboxState(),
                key: `item-${item.detail_product_id}-${Date.now()}-${Math.random()}`,
                detail_product_id: item.detail_product_id,
                qty: Number(item.qty) || 1,
                unit_price: Number(item.unit_price) || 0,
                line_total: Number(item.line_total) || 0,
                product_name: item.product_name,
                expired_label: item.expired_label,
            };
        },

        createEditItemFromOption(option) {
            const qty = 1;
            const unitPrice = Number(option.price) || 0;

            return {
                ...this.createEditItemComboboxState(),
                key: `item-${option.id}-${Date.now()}-${Math.random()}`,
                detail_product_id: option.id,
                qty,
                unit_price: unitPrice,
                line_total: qty * unitPrice,
                product_name: option.name,
                expired_label: option.expired_label,
            };
        },

        createEditItemComboboxState() {
            return {
                productSearchMode: false,
                productQuery: "",
                productDropdownOpen: false,
                productHighlightIndex: -1,
            };
        },

        getProductOptionById(id) {
            return this.productOptions.find(
                (product) => product.id === Number(id),
            );
        },

        formatProductOptionLabel(option) {
            return `${option.name} • Exp ${option.expired_label} • ${option.stock} pcs`;
        },

        formatProductLabel(item) {
            const option = this.getProductOptionById(item.detail_product_id);

            if (option) {
                return this.formatProductOptionLabel(option);
            }

            if (item.product_name) {
                return `${item.product_name} • Exp ${item.expired_label || "-"}`;
            }

            return "Pilih produk";
        },

        getFilteredProductOptions(item) {
            const query = (item.productQuery || "").trim().toLowerCase();

            if (query === "") {
                return this.productOptions;
            }

            return this.productOptions.filter((option) => {
                const label =
                    `${option.name} ${option.expired_label} ${option.stock}`.toLowerCase();

                return label.includes(query);
            });
        },

        closeAllProductDropdowns(exceptItem = null) {
            this.editForm?.items?.forEach((editItem) => {
                if (exceptItem && editItem.key === exceptItem.key) {
                    return;
                }

                editItem.productDropdownOpen = false;
                editItem.productHighlightIndex = -1;
            });
        },

        closeProductDropdown(item) {
            item.productDropdownOpen = false;
            item.productHighlightIndex = -1;

            if (item.productSearchMode && item.detail_product_id) {
                item.productSearchMode = false;
                item.productQuery = "";
            }
        },

        openProductSearch(item) {
            this.closeAllProductDropdowns();
            item.productSearchMode = true;
            item.productQuery = "";
            item.productDropdownOpen = true;
            item.productHighlightIndex =
                this.productOptions.length > 0 ? 0 : -1;

            this.$nextTick(() => {
                document
                    .getElementById(`product-search-${item.key}`)
                    ?.focus();
            });
        },

        onEditProductQueryInput(item) {
            this.closeAllProductDropdowns(item);
            item.productDropdownOpen = true;

            const filtered = this.getFilteredProductOptions(item);
            item.productHighlightIndex = filtered.length > 0 ? 0 : -1;
        },

        onEditProductSearchFocus(item) {
            this.closeAllProductDropdowns(item);
            item.productDropdownOpen = true;

            const filtered = this.getFilteredProductOptions(item);
            item.productHighlightIndex = filtered.length > 0 ? 0 : -1;
        },

        selectEditProduct(item, option) {
            item.detail_product_id = option.id;
            this.onEditProductChange(item);
            item.productSearchMode = false;
            item.productQuery = "";
            item.productDropdownOpen = false;
            item.productHighlightIndex = -1;
        },

        onEditProductKeydown(event, item) {
            const filtered = this.getFilteredProductOptions(item);

            if (event.key === "ArrowDown") {
                event.preventDefault();
                item.productDropdownOpen = true;

                if (filtered.length === 0) {
                    return;
                }

                item.productHighlightIndex = Math.min(
                    item.productHighlightIndex + 1,
                    filtered.length - 1,
                );

                if (item.productHighlightIndex < 0) {
                    item.productHighlightIndex = 0;
                }

                return;
            }

            if (event.key === "ArrowUp") {
                event.preventDefault();
                item.productDropdownOpen = true;

                if (filtered.length === 0) {
                    return;
                }

                item.productHighlightIndex = Math.max(
                    item.productHighlightIndex - 1,
                    0,
                );

                return;
            }

            if (event.key === "Enter") {
                if (
                    !item.productDropdownOpen ||
                    item.productHighlightIndex < 0 ||
                    !filtered[item.productHighlightIndex]
                ) {
                    return;
                }

                event.preventDefault();
                this.selectEditProduct(
                    item,
                    filtered[item.productHighlightIndex],
                );

                return;
            }

            if (event.key === "Escape") {
                event.preventDefault();
                this.closeProductDropdown(item);
            }
        },

        async enterEditMode() {
            if (!this.selectedTransaction || this.editMode) {
                return;
            }

            try {
                await this.loadProductOptions();

                if (this.productOptions.length === 0) {
                    this.showToast(
                        "error",
                        "Gagal",
                        "Tidak ada produk tersedia untuk diedit.",
                    );
                    return;
                }

                const items = (this.selectedTransaction.items || []).map(
                    (item) => this.createEditItemFromTransactionItem(item),
                );

                this.editForm = {
                    selectedCustomer: {
                        id: this.selectedTransaction.customer_id,
                        nama_pelanggan: this.selectedTransaction.customer,
                        no_telp: this.selectedTransaction.phone || "",
                    },
                    customerQuery: "",
                    items: items.length > 0 ? items : [this.createEditItemFromOption(this.productOptions[0])],
                };

                this.editCustomerDropdownOpen = false;
                this.editCustomerResults = [];
                this.editMode = true;
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    "Gagal memuat data edit transaksi.",
                );
            }
        },

        exitEditMode() {
            this.editMode = false;
            this.editForm = null;
            this.productOptions = [];
            this.savingEdit = false;
            this.editCustomerDropdownOpen = false;
            this.editCustomerResults = [];
            this.editCustomerSearchLoading = false;
            clearTimeout(this.editCustomerSearchTimer);
            this.editCustomerSearchAbort?.abort();
            this.editCustomerSearchAbort = null;
        },

        addEditItem() {
            if (!this.editForm || this.productOptions.length === 0) {
                return;
            }

            this.editForm.items.push(
                this.createEditItemFromOption(this.productOptions[0]),
            );
        },

        removeEditItem(index) {
            if (!this.editForm || this.editForm.items.length <= 1) {
                return;
            }

            this.editForm.items.splice(index, 1);
        },

        onEditProductChange(item) {
            const option = this.productOptions.find(
                (product) => product.id === Number(item.detail_product_id),
            );

            if (!option) {
                return;
            }

            item.product_name = option.name;
            item.expired_label = option.expired_label;

            if (!item.unit_price || item.unit_price <= 0) {
                item.unit_price = Number(option.price) || 0;
            }

            this.recalculateEditItem(item);
        },

        recalculateEditItem(item) {
            const qty = Math.max(1, parseInt(item.qty, 10) || 1);
            const unitPrice = Math.max(0, parseInt(item.unit_price, 10) || 0);

            item.qty = qty;
            item.unit_price = unitPrice;
            item.line_total = qty * unitPrice;
        },

        onEditCustomerQueryInput() {
            if (
                this.editForm?.selectedCustomer &&
                this.editForm.customerQuery.trim() !==
                    this.editForm.selectedCustomer.nama_pelanggan.trim()
            ) {
                this.editForm.selectedCustomer = null;
            }

            this.searchEditCustomers(true);
        },

        onEditCustomerSearchFocus() {
            if (
                this.editForm?.selectedCustomer ||
                this.editForm?.customerQuery.trim() === ""
            ) {
                return;
            }

            this.editCustomerDropdownOpen = true;
            this.searchEditCustomers(true);
        },

        searchEditCustomers(immediate = false) {
            clearTimeout(this.editCustomerSearchTimer);

            const query = this.editForm?.customerQuery?.trim() || "";

            if (query === "") {
                this.editCustomerResults = [];
                this.editCustomerDropdownOpen = false;
                this.editCustomerSearchLoading = false;
                this.editCustomerSearchAbort?.abort();
                this.editCustomerSearchAbort = null;
                return;
            }

            this.editCustomerDropdownOpen = true;
            this.editCustomerSearchLoading = true;

            const runSearch = async () => {
                this.editCustomerSearchAbort?.abort();

                const controller = new AbortController();
                this.editCustomerSearchAbort = controller;

                try {
                    const response = await fetch(
                        `/customers/search?q=${encodeURIComponent(query)}`,
                        {
                            signal: controller.signal,
                        },
                    );

                    if (controller.signal.aborted) {
                        return;
                    }

                    if (!response.ok) {
                        this.editCustomerResults = [];
                        return;
                    }

                    this.editCustomerResults = await response.json();
                } catch (error) {
                    if (error.name !== "AbortError") {
                        this.editCustomerResults = [];
                    }
                } finally {
                    if (this.editCustomerSearchAbort === controller) {
                        this.editCustomerSearchLoading = false;
                        this.editCustomerSearchAbort = null;
                    }
                }
            };

            if (immediate) {
                runSearch();
                return;
            }

            this.editCustomerSearchTimer = setTimeout(runSearch, 150);
        },

        selectEditCustomer(customer) {
            this.editCustomerSearchAbort?.abort();
            this.editCustomerSearchAbort = null;
            this.editCustomerSearchLoading = false;

            this.editForm.selectedCustomer = customer;
            this.editForm.customerQuery = customer.nama_pelanggan;
            this.editCustomerResults = [];
            this.editCustomerDropdownOpen = false;
        },

        clearEditCustomer() {
            this.editForm.selectedCustomer = null;
            this.editForm.customerQuery = "";
            this.editCustomerResults = [];
            this.editCustomerDropdownOpen = false;
        },

        validateEditForm() {
            if (!this.editForm?.selectedCustomer) {
                this.showToast(
                    "error",
                    "Validasi",
                    "Pilih pelanggan terlebih dahulu.",
                );
                return false;
            }

            if (!this.editForm.items.length) {
                this.showToast(
                    "error",
                    "Validasi",
                    "Minimal harus ada satu produk.",
                );
                return false;
            }

            for (const item of this.editForm.items) {
                if (!item.detail_product_id) {
                    this.showToast(
                        "error",
                        "Validasi",
                        "Semua produk harus dipilih.",
                    );
                    return false;
                }

                if (!item.qty || item.qty < 1) {
                    this.showToast(
                        "error",
                        "Validasi",
                        "Jumlah produk minimal 1 PCS.",
                    );
                    return false;
                }

                if (item.unit_price < 0) {
                    this.showToast(
                        "error",
                        "Validasi",
                        "Harga jual tidak valid.",
                    );
                    return false;
                }
            }

            return true;
        },

        requestSaveEdit() {
            if (this.savingEdit || !this.validateEditForm()) {
                return;
            }

            this.openConfirm(
                "Apakah Anda yakin ingin menyimpan perubahan ini?",
                () => this.saveEdit(),
            );
        },

        applyUpdatedTransaction(updatedTransaction) {
            const index = this.transactions.findIndex(
                (transaction) => transaction.id === updatedTransaction.id,
            );

            if (index !== -1) {
                this.transactions[index] = updatedTransaction;
            }

            this.selectedTransaction = updatedTransaction;
            this.transactionCount = this.transactions.length;
            this.totalAmount = this.transactions.reduce(
                (sum, transaction) => sum + (transaction.amount || 0),
                0,
            );
        },

        async saveEdit() {
            if (this.savingEdit || !this.selectedTransaction || !this.editForm) {
                return;
            }

            if (!this.validateEditForm()) {
                return;
            }

            this.savingEdit = true;

            try {
                const response = await fetch(
                    `/transactions/${this.selectedTransaction.id}`,
                    {
                        method: "PUT",
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": this.csrfToken(),
                        },
                        body: JSON.stringify({
                            customer_id: this.editForm.selectedCustomer.id,
                            items: this.editForm.items.map((item) => ({
                                detail_product_id: item.detail_product_id,
                                harga_jual: item.unit_price,
                                pcs: item.qty,
                            })),
                        }),
                    },
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            Object.values(data.errors || {})
                                .flat()
                                .join(" ") ||
                            "Gagal menyimpan perubahan",
                    );
                }

                this.applyUpdatedTransaction(data.transaction);
                this.exitEditMode();

                this.showToast(
                    "success",
                    "Berhasil",
                    "Transaksi berhasil diperbarui.",
                );
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    error.message || "Gagal menyimpan perubahan transaksi.",
                );
            } finally {
                this.savingEdit = false;
            }
        },

        requestDeleteTransaction() {
            if (this.deletingTransaction || !this.selectedTransaction) {
                return;
            }

            this.openConfirm(
                "Apakah Anda yakin ingin menghapus transaksi ini?",
                () => this.deleteTransaction(),
            );
        },

        async deleteTransaction() {
            if (this.deletingTransaction || !this.selectedTransaction) {
                return;
            }

            this.deletingTransaction = true;

            try {
                const response = await fetch(
                    `/transactions/${this.selectedTransaction.id}`,
                    {
                        method: "DELETE",
                        headers: {
                            Accept: "application/json",
                            "X-CSRF-TOKEN": this.csrfToken(),
                        },
                    },
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || "Gagal menghapus transaksi");
                }

                this.transactions = this.transactions.filter(
                    (transaction) =>
                        transaction.id !== this.selectedTransaction.id,
                );
                this.transactionCount = this.transactions.length;
                this.totalAmount = this.transactions.reduce(
                    (sum, transaction) => sum + (transaction.amount || 0),
                    0,
                );

                this.closeDetail();

                this.showToast(
                    "success",
                    "Berhasil",
                    "Transaksi berhasil dihapus.",
                );
            } catch (error) {
                console.error(error);
                this.showToast(
                    "error",
                    "Gagal",
                    error.message || "Gagal menghapus transaksi.",
                );
            } finally {
                this.deletingTransaction = false;
            }
        },

    };
}
