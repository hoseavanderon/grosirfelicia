<p class="cart-price-line">
    Rp
    <input type="text" inputmode="numeric" class="price-control-input" :value="getPriceInputValue(item)"
        @input="onPriceInput(item, $event.target.value)" @focus="focusedPriceId = item.id"
        @blur="onPriceBlur(item, $event.target)" @wheel.prevent @keydown.enter="$event.target.blur()">
    / pcs
</p>
