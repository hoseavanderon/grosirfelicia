@forelse ($detailProducts as $detail)
    <li class="list-group-item d-flex justify-content-between align-items-center"
        data-name="{{ $detail->product->nama_produk }}" data-price="{{ $detail->product->harga_jual }}"
        data-id="{{ $detail->product_id }}" data-expired="{{ \Carbon\Carbon::parse($detail->expired)->format('d/m') }}"
        data-detail-product-id="{{ $detail->id }}">

        <strong class="text-uppercase">
            {{ $detail->product->nama_produk }}
            (<bold class="text-danger">{{ \Carbon\Carbon::parse($detail->expired)->format('d / m') }} =>
                {{ $detail->stok }} Pcs</bold>)
        </strong>

        <strong class="text-uppercase">
            Rp {{ number_format($detail->product->harga_jual, 0, ',', '.') }}
        </strong>
    </li>
@empty
    <li class="list-group-item text-center text-muted">Tidak ada produk ditemukan.</li>
@endforelse
