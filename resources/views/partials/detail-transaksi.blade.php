@php
    $transactions = $customer->transactions;
@endphp

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.min.css" rel="stylesheet">

@if ($transactions->isEmpty())
    <div class="alert alert-warning text-center">
        Tidak ada transaksi pada rentang tanggal yang dipilih.
    </div>
@else
    @php
        $totalPenjualan = $transactions->sum('subtotal');
        $totalOrder = $transactions->count();
        $totalItem = $transactions->sum('jumlah_item');
        $largestSubtotal = $transactions->max('subtotal');
    @endphp

    @if (request('tanggal_transaksi'))
        @php
            $tanggalParts = explode(' to ', str_replace(' sampai ', ' to ', request('tanggal_transaksi')));
        @endphp
        @if (count($tanggalParts) === 2)
            <div class="alert alert-info text-center">
                Menampilkan data dari <strong>{{ $tanggalParts[0] }}</strong> sampai
                <strong>{{ $tanggalParts[1] }}</strong>
            </div>
        @endif
    @endif

    <div class="row">
        <!-- Card: Total Penjualan -->
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                    <i class="far fa-newspaper"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Penjualan</h4>
                    </div>
                    <div class="card-body">
                        Rp {{ number_format($totalPenjualan, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Total Orders -->
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Orders</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalOrder }} Order
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Total Items Ordered -->
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Items</h4>
                    </div>
                    <div class="card-body">
                        {{ $totalItem }} pcs
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Largest Subtotal -->
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Transaksi Terbesar</h4>
                    </div>
                    <div class="card-body">
                        Rp {{ number_format($largestSubtotal, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Order</th>
                    <th>Subtotal</th>
                    <th>Total Item</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $key => $trx)
                    <tr>
                        <td>{{ $key + 1 }}.</td>
                        <td>{{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d F Y') }}</td>
                        <td>Rp {{ number_format($trx->subtotal, 0, ',', '.') }}</td>
                        <td>{{ $trx->jumlah_item }} pcs</td>
                        <td>
                            <a href="#"
                                class="badge bg-primary text-decoration-none text-white btn-detail-transaksi"
                                data-toggle="modal" data-target="#modalDetailTransaksi"
                                data-transaksi='@json($trx)'>
                                <i class="fas fa-eye me-1"></i>
                            </a>
                            <a href="#"
                                class="badge bg-danger text-decoration-none text-white btn-delete-transaksi"
                                data-id="{{ $trx->id }}" data-transaksi='@json($trx)'>
                                <i class="fas fa-trash me-1"></i>
                            </a>
                            <a href="{{ route('kirim.whatsapp', $trx->id) }}" target="_blank"
                                class="badge bg-success text-white text-decoration-none">
                                <i class="fas fa-paper-plane me-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<div class="modal fade" id="modalDetailTransaksi" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-3" id="modalDetailContent">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center">
                    <p class="font-weight-bold">NOTA TRANSAKSI</p>
                    <p id="waktuTransaksi"></p>
                    <p>TRX: <span id="nomorNota"></span></p>
                </div>

                <div class="border-top border-bottom py-2">
                    <div class="d-flex justify-content-between">
                        <strong>Pelanggan:</strong> <span id="namaPelanggan"></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>No Telp:</strong> <span id="noTelp"></span>
                    </div>
                </div>

                <div id="daftarProduk" class="my-2"></div>

                <hr>
                <div class="text-right font-weight-bold">
                    Total: Rp <span id="totalTransaksi"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.5/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('click', function(event) {
            if (event.target.closest('.btn-detail-transaksi')) {
                const button = event.target.closest('.btn-detail-transaksi');
                const data = JSON.parse(button.getAttribute('data-transaksi'));

                // Format data transaksi seperti sebelumnya
                const createdAt = new Date(data.created_at);
                const optionsTanggal = {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                };
                const tanggal = createdAt.toLocaleDateString('id-ID', optionsTanggal);
                const jam = createdAt.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                document.getElementById('waktuTransaksi').textContent = `${tanggal} ${jam}`;
                document.getElementById('nomorNota').textContent = data.nomor_nota ?? 'N/A';
                document.getElementById('namaPelanggan').textContent = (data.customer?.nama_pelanggan ?? '-')
                    .toUpperCase();
                document.getElementById('noTelp').textContent = data.customer?.no_telp ?? '-';

                const produkContainer = document.getElementById('daftarProduk');
                produkContainer.innerHTML = '';
                let total = 0;

                if (data.detail_transactions) {
                    data.detail_transactions.forEach(item => {
                        const namaProduk = item.detail_product?.product?.nama_produk ??
                            'Produk tidak ditemukan';
                        const expired = item.detail_product?.expired ?
                            new Date(item.detail_product.expired).toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: '2-digit'
                            }) : '';
                        const subtotal = item.pcs * item.harga_jual;
                        total += subtotal;

                        produkContainer.innerHTML += `
                    <div class="d-flex justify-content-between">
                        <div>
                            <div>${namaProduk} (${expired})</div>
                            <div>${item.pcs.toLocaleString('id-ID')} x Rp ${item.harga_jual.toLocaleString('id-ID')}</div>
                        </div>
                        <div class="text-right">Rp ${subtotal.toLocaleString('id-ID')}</div>
                    </div>`;
                    });
                }

                document.getElementById('totalTransaksi').textContent = total.toLocaleString('id-ID');

                $('#modalDetailTransaksi').on('shown.bs.modal', function() {
                    $('.modal-backdrop').remove(); // paksa hilangkan backdrop
                });
            }
        });

        document.addEventListener('click', function(event) {
            if (event.target.closest('.btn-delete-transaksi')) {
                const button = event.target.closest('.btn-delete-transaksi');
                const transaksiId = button.getAttribute('data-id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Transaksi ini akan dihapus dan stok produk akan dikembalikan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/hapus-transaksi/${transaksiId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire(
                                        'Terhapus!',
                                        data.message,
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Gagal!',
                                        'Terjadi kesalahan saat menghapus transaksi.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                });
            }
        });
    </script>
@endpush
