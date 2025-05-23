<div id="print-area" class="text-sm font-mono space-y-2">
    <div class="text-sm font-mono space-y-2">
        <div class="text-center">
            <p class="font-bold text-base">NOTA TRANSAKSI</p>
            <p>{{ \Carbon\Carbon::parse($record->created_at)->format('d M Y H:i') }}</p>
            <p>TRX: {{ $record->nomor_nota ?? 'N/A' }}</p>
        </div>

        <div class="border-t border-b py-2 text-xs space-y-1">
            <div class="flex justify-between">
                <span><strong>Pelanggan:</strong></span>
                <span>{{ strtoupper($record->customer->nama_pelanggan ?? '-') }}</span>
            </div>
            @if ($record->customer->no_telp)
                <div class="flex justify-between">
                    <span><strong>No Telp:</strong></span>
                    <span>{{ $record->customer->no_telp }}</span>
                </div>
            @endif
        </div>

        <div class="space-y-1 text-xs">
            @foreach ($record->detailTransactions as $item)
                <div class="flex justify-between">
                    {{-- Kiri --}}
                    <div>
                        @if ($item->detailProduct && $item->detailProduct->product)
                            <div>{{ $item->detailProduct->product->nama_produk }}
                                ({{ \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m') }})
                            </div>
                            <div>{{ $item->pcs }} x Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</div>
                        @else
                            <div>Produk tidak ditemukan</div>
                        @endif
                    </div>

                    {{-- Kanan (subtotal) --}}
                    <div class="text-right">
                        <div>Rp {{ number_format($item->harga_jual * $item->pcs, 0, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach
        </div>


        <hr class="my-2 border-t-2">

        <div class="text-right text-base font-bold">
            Total: Rp
            {{ number_format($record->detailTransactions->sum(fn($i) => $i->pcs * $i->harga_jual), 0, ',', '.') }}
        </div>
    </div>
</div>
