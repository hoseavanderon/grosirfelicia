<ul class="list-group">
    @foreach ($products as $product)
        <li class="list-group-item mt-3">
            <div class="d-flex justify-content-between">
                <strong>
                    {{ $product->nama_produk }}
                    ({{ $product->detailProducts->sum('stok') }} pcs)
                </strong>
                <span>Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</span>
            </div>

            @foreach ($product->detailProducts as $detail)
                <div class="badge mt-2 {{ $detail->stok <= 20 ? 'badge-danger' : 'badge-primary' }}">
                    {{ \Carbon\Carbon::parse($detail->expired)->format('d / m') }} =>
                    {{ $detail->stok }} pcs
                </div>
            @endforeach

            @if ($product->detailProducts->isEmpty())
                <div class="text-muted">Belum ada detail produk</div>
            @endif
        </li>
    @endforeach

    @if ($products->isEmpty())
        <div class="text-muted text-center">Belum ada produk</div>
    @endif
</ul>
