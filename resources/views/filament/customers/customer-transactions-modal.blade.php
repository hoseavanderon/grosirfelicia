<div class="overflow-x-auto">
    <div x-data="{ open: false, selectedTransaction: null }">
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-dark-100">
                    <th class="border border-gray-300 px-4 py-2">#</th>
                    <th class="border border-gray-300 px-4 py-2">No. Nota</th>
                    <th class="border border-gray-300 px-4 py-2">Tanggal</th>
                    <th class="border border-gray-300 px-4 py-2">Subtotal</th>
                    <th class="border border-gray-300 px-4 py-2">Total Item</th>
                    <th class="border border-gray-300 px-4 py-2">Metode</th>
                    <th class="border border-gray-300 px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $transaction)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-4 py-2 font-mono text-blue-600">
                            {{ $transaction->nomor_nota ?? '-' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y') }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            Rp
                            {{ number_format($transaction->detailTransactions->sum(fn($d) => $d->pcs * $d->harga_jual), 0, ',', '.') }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ $transaction->detailTransactions->sum('pcs') }} pcs
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ ucfirst($transaction->metode_pembayaran) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ str_replace('_', ' ', $transaction->is_paid) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                            Tidak ada transaksi
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
