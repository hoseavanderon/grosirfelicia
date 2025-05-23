@php
    \Carbon\Carbon::setLocale('id');
@endphp

@if ($transactions->isEmpty())
    <div class="alert alert-info text-center">
        Tidak ada transaksi {{ $startDate->isToday() ? 'hari ini' : 'pada rentang tanggal yang dipilih' }}.
    </div>
@else
    {{-- Logika dan Tampilan Transaksi --}}
    @php
        $startDateOnly = $startDate->toDateString();
        $endDateOnly = $endDate->toDateString();
        $today = \Carbon\Carbon::today()->toDateString();

        $grouped = $transactions->groupBy(fn($trx) => $trx->created_at->toDateString());
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap pt-3">
        <h6 class="text-muted mb-2 mb-md-0">
            @if ($startDateOnly === $endDateOnly && $startDateOnly === $today)
                Hari ini
            @elseif ($startDateOnly === $endDateOnly)
                {{ $startDate->translatedFormat('l, d F Y') }}
            @else
                {{ $startDate->translatedFormat('l, d F Y') }} - {{ $endDate->translatedFormat('l, d F Y') }}
            @endif
        </h6>
        <div>
            <strong>Uang Diterima:</strong> Rp {{ number_format($totalUangDiterima, 0, ',', '.') }}
        </div>
    </div>

    {{-- Tampilkan Kartu Transaksi --}}
    @foreach ($grouped as $tanggal => $trxs)
        <h5 class="mt-4 mb-3">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}</h5>

        @foreach ($trxs as $transaction)
            @php
                $totalTransaksi = $transaction->detailTransactions->sum(fn($d) => $d->harga_jual * $d->pcs);
            @endphp
            <div class="card mb-2" data-toggle="modal" data-target="#modalDetail-{{ $transaction->id }}"
                data-backdrop="false">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-uppercase">{{ $transaction->customer->nama_pelanggan }}</small>
                        <div class="font-weight-bold">Rp {{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                        <small>{{ $transaction->created_at->format('H:i') }} — {{ $transaction->nomor_nota }}</small>
                    </div>
                    <span class="badge badge-pill badge-success">
                        {{ $transaction->status ?? 'lunas' }}
                    </span>
                </div>
            </div>
        @endforeach
    @endforeach
@endif

{{-- 🟢 MODAL DIPINDAHKAN KE BAGIAN AKHIR FILE BLADE --}}
@foreach ($transactions as $transaction)
    <div class="modal fade" id="modalDetail-{{ $transaction->id }}" tabindex="-1" role="dialog"
        aria-labelledby="modalDetailLabel{{ $transaction->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title w-100 text-center">NOTA TRANSAKSI</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="closeModal('{{ $transaction->id }}')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center text-muted">
                        {{ $transaction->created_at->format('d M Y H:i') }}<br>
                        <strong>TRX: {{ $transaction->nomor_nota }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div><strong>Pelanggan:</strong></div>
                        <div class="text-uppercase">{{ $transaction->customer->nama_pelanggan }}</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div><strong>No Telp:</strong></div>
                        <div>{{ $transaction->customer->no_telp }}</div>
                    </div>
                    <hr>

                    @foreach ($transaction->detailTransactions as $item)
                        <div class="mb-1 d-flex justify-content-between">
                            <div class="text-uppercase">
                                {{ $item->detailProduct->product->nama_produk }}
                                ({{ \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m') }})
                                <br>
                                {{ $item->pcs }} x Rp {{ number_format($item->harga_jual, 0, ',', '.') }}
                            </div>
                            <div class="text-right font-weight-bold" style="white-space: nowrap;">
                                Rp {{ number_format($item->pcs * $item->harga_jual, 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach

                    <hr>
                    <div class="d-flex justify-content-end">
                        <strong>Total: Rp
                            {{ number_format($transaction->detailTransactions->sum(fn($d) => $d->pcs * $d->harga_jual), 0, ',', '.') }}</strong>
                    </div>

                    <hr>
                    <div class="text-center mt-3">
                        @php
                            $message = "Detail Nota :\n";
                            $message .= 'Nomor Nota: ' . ($transaction->nomor_nota ?? 'N/A') . "\n";
                            $message .=
                                'Pelanggan: ' . strtoupper($transaction->customer->nama_pelanggan ?? '-') . "\n";
                            $totalPcs = $transaction->detailTransactions->sum('pcs');
                            $message .= "Total Item: $totalPcs pcs\n";
                            $message .= "========================\n";

                            foreach ($transaction->detailTransactions as $item) {
                                $productName = $item->detailProduct->product->nama_produk;
                                $expired = \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m');
                                $pcs = $item->pcs;
                                $price = number_format($item->harga_jual, 0, ',', '.');
                                $message .= "$productName (Exp: $expired) - $pcs x Rp $price\n";
                            }

                            $message .= "========================\n";
                            $message .=
                                '*Total: Rp ' .
                                number_format(
                                    $transaction->detailTransactions->sum(fn($i) => $i->pcs * $i->harga_jual),
                                    0,
                                    ',',
                                    '.',
                                ) .
                                "*\n";
                            $message .= "\nTerima kasih atas pembelian Anda!";
                            $waLink = "https://wa.me/{$transaction->customer->no_telp}?text=" . urlencode($message);
                        @endphp

                        <a href="{{ $waLink }}" target="_blank" class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Kirim via WhatsApp
                        </a>

                        <a href="{{ route('riwayat-transaksi.edit', $transaction->id) }}" class="btn btn-warning">
                            <i class="fas fa-pen"></i> Edit
                        </a>

                        <form action="{{ route('riwayat-transaksi.destroy', $transaction->id) }}" method="POST"
                            style="display: inline;" onsubmit="return confirmDelete(event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach


@push('scripts')
@endpush
