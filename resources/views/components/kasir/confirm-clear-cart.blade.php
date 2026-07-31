<x-ui.modal show="confirmClearCartModal" maxWidth="md">

    <div class="confirm-checkout-body confirm-clear-cart-body">

        <h3 class="confirm-checkout-title">
            Apakah anda yakin ingin menghapus semua item di keranjang?
        </h3>

        <div class="confirm-checkout-actions confirm-clear-cart-actions">

            <button type="button" @click="confirmClearCartModal = false"
                class="confirm-checkout-btn confirm-checkout-cancel confirm-clear-cart-btn">
                Batal
            </button>

            <button type="button" @click="clearCart()"
                class="confirm-checkout-btn confirm-checkout-danger confirm-clear-cart-btn">
                Yes, Kosongkan
            </button>

        </div>

    </div>

</x-ui.modal>
