<div class="customer-search-wrapper" @click.outside="!selectedCustomer && (customerDropdownOpen = false)">

    <label class="kasir-label">
        Nama Toko
    </label>

    <div class="customer-field" :class="{ 'is-selected': selectedCustomer }">

        <input x-show="!selectedCustomer" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-[0.98]" type="text" class="customer-input"
            placeholder="Cari nama toko..." x-model="customerQuery" x-ref="customerInput"
            @input="onCustomerQueryInput()" @focus="onCustomerSearchFocus()" autocomplete="off">

        <div x-show="selectedCustomer" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-[0.98]" class="customer-input customer-input--selected"
            tabindex="0" x-ref="customerSelected" @keydown.backspace.prevent="clearCustomerSelection()">

            <span class="customer-selected-name" x-text="selectedCustomer?.nama_pelanggan"></span>

            <button type="button" class="customer-clear-btn" @click.stop="clearCustomerSelection()"
                aria-label="Hapus pilihan toko" title="Ganti toko">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

        </div>

    </div>

    <div x-show="!selectedCustomer && customerDropdownOpen && customerQuery.trim() !== ''" x-transition
        class="customer-dropdown">

        <div x-show="customerSearchLoading" class="customer-dropdown-loading">

            <span class="customer-search-spinner" aria-hidden="true"></span>

        </div>

        <template x-if="!customerSearchLoading">

            <div>

                <template x-for="customer in customerResults" :key="customer.id">

                    <button type="button" class="customer-dropdown-item" @click="selectCustomer(customer)">

                        <span class="customer-dropdown-name" x-text="customer.nama_pelanggan"></span>

                    </button>

                </template>

                <div x-show="customerResults.length === 0" class="customer-dropdown-status">
                    Tidak ditemukan
                </div>

            </div>

        </template>

    </div>

</div>
