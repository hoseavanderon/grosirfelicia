<x-ui.modal show="confirmClearCartModal" maxWidth="md">

    <div class="confirm-checkout-body">

        <h3 class="confirm-checkout-title">
            Apakah anda yakin ingin menghapus semua item di keranjang?
        </h3>

        <div class="confirm-checkout-actions">

            <button type="button" @click="confirmClearCartModal = false"
                class="confirm-checkout-btn confirm-checkout-cancel">
                Batal
            </button>

            <button type="button" @click="clearCart()" class="confirm-checkout-btn confirm-checkout-danger">
                Yes, Kosongkan
            </button>

        </div>

    </div>

</x-ui.modal>
