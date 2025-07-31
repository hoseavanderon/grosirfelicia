@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Riwayat Barang Masuk</h1>
            <a href="{{ route('barang-masuk') }}" class="btn btn-secondary ml-auto">← Kembali</a>
        </div>

        <div class="section-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal Masuk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tanggalUnik as $tanggal)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tanggal->tanggal_masuk)->translatedFormat('d F Y') }}</td>
                            <td>
                                <button class="btn btn-info btn-sm btn-lihat-detail"
                                    data-tanggal="{{ $tanggal->tanggal_masuk }}">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">Tidak ada riwayat barang masuk</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Barang Masuk - <span id="modalTanggal"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Jumlah Masuk</th>
                            </tr>
                        </thead>
                        <tbody id="modalDetailBody">
                            <tr>
                                <td colspan="3" class="text-center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).on('click', '.btn-lihat-detail', function() {
            const tanggal = $(this).data('tanggal');
            $('#modalTanggal').text(
                new Date(tanggal).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                })
            );
            $('#modalDetailBody').html('<tr><td colspan="3" class="text-center">Memuat data...</td></tr>');

            $('#modalDetail').modal('show');

            $.get(`/barang-masuk/riwayat/${tanggal}`, function(data) {
                if (data.length > 0) {
                    let rows = '';
                    data.forEach(item => {
                        const expired = item.detail_product?.expired ?
                            new Date(item.detail_product.expired).toLocaleDateString('id-ID') :
                            '-';

                        rows += `
                            <tr>
                                <td>
                                    ${item.detail_product?.product?.nama_produk ?? '-'}<br>
                                    <small class="text-muted">Exp: ${expired}</small>
                                </td>
                                <td>${item.jumlah_masuk} Pcs</td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-hapus-riwayat" data-id="${item.id}" data-jumlah="${item.jumlah_masuk}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>`;
                    });
                    $('#modalDetailBody').html(rows);
                } else {
                    $('#modalDetailBody').html(
                        '<tr><td colspan="3" class="text-center">Tidak ada data.</td></tr>');
                }
            }).fail(() => {
                $('#modalDetailBody').html(
                    '<tr><td colspan="3" class="text-danger text-center">Gagal memuat data.</td></tr>');
            });
        });

        $(document).on('click', '.btn-hapus-riwayat', function() {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Yakin ingin hapus?',
                text: 'Stok akan dikurangi secara otomatis.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/barang-masuk/riwayat/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Riwayat berhasil dihapus dan stok dikurangi.',
                                    icon: 'success',
                                    timer: 500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                // Jika server merespon success: false
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: res.message || 'Gagal menghapus riwayat.',
                                    icon: 'error'
                                });
                                console.log('Server responded with error:', res);
                            }
                        },
                        error: function(xhr, status, error) {
                            // 🔍 Debug lengkap error dari server
                            console.log('AJAX error response:', xhr);
                            console.log('Status:', status);
                            console.log('Error:', error);
                            console.log('Response Text:', xhr.responseText);

                            Swal.fire({
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan saat menghapus riwayat.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush
