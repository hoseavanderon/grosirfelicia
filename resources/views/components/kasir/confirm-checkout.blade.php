<x-ui.modal show="confirmCheckoutModal" maxWidth="md">

    <div class="confirm-checkout-body">

        <h3 class="confirm-checkout-title">
            Apakah Anda yakin ingin menyelesaikan transaksi ini?
        </h3>

        <div class="confirm-checkout-actions">

            <button type="button" @click="confirmCheckoutModal = false"
                class="confirm-checkout-btn confirm-checkout-cancel" :disabled="checkoutLoading">
                Batal
            </button>

            <button type="button" @click="processCheckout()" class="confirm-checkout-btn confirm-checkout-confirm"
                :disabled="checkoutLoading">
                <span x-show="!checkoutLoading">Yes, Lanjutkan</span>
                <span x-show="checkoutLoading">Memproses...</span>
            </button>

        </div>

    </div>

</x-ui.modal>
