@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h1>Barang Masuk</h1>
            <a href="{{ route('barang-masuk.riwayat') }}" class="btn btn-info">Riwayat Barang Masuk</a>
        </div>

        <div class="section-body">
            <div class="tab-content">
                {{-- Tab Barang Sudah Ada --}}
                <div class="tab-pane fade show active" id="existing">
                    <form id="form-existing">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah (pcs)</th>
                                    <th>Aksi</th> {{-- Hapus kolom Harga --}}
                                </tr>
                            </thead>
                            <tbody id="existing-body">
                                <tr>
                                    <td>
                                        <select name="items[0][detail_product_id]" class="form-control" required>
                                            <option value="">-- Pilih Detail Produk --</option>
                                            @foreach ($detailProducts as $dp)
                                                <option value="{{ $dp->id }}">
                                                    {{ $dp->product->nama_produk }} - Stok: {{ $dp->stok }} - Exp:
                                                    {{ $dp->expired ? \Carbon\Carbon::parse($dp->expired)->format('d-m-Y') : '-' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[0][pcs]" class="form-control" required></td>
                                    <td><button type="button" class="btn btn-danger btn-remove">Hapus</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="d-flex">
                            <button type="button" class="btn btn-secondary" id="add-existing-row">+ Tambah Baris</button>
                            <button type="submit" class="btn btn-primary ml-3">Simpan Barang Masuk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let existingIndex = 1;
        let newIndex = 1;

        // Tambah baris untuk produk yang sudah ada
        $('#add-existing-row').click(function() {
            $('#existing-body').append(`
                <tr>
                    <td>
                        <select name="items[${existingIndex}][detail_product_id]" class="form-control" required>
                            <option value="">-- Pilih Detail Produk --</option>
                            @foreach ($detailProducts as $dp)
                                <option value="{{ $dp->id }}">
                                    {{ $dp->product->nama_produk }} - Exp: {{ $dp->expired ? \Carbon\Carbon::parse($dp->expired)->format('d-m-Y') : '-' }} - Stok: {{ $dp->stok }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="items[${existingIndex}][pcs]" class="form-control" required></td>
                    <td><button type="button" class="btn btn-danger btn-remove">Hapus</button></td>
                </tr>
            `);
            existingIndex++;
        });

        // Hapus baris dinamis
        $(document).on('click', '.btn-remove', function() {
            $(this).closest('tr').remove();
        });

        // Submit produk lama
        $('#form-existing').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Tambah Stok?',
                text: 'Stok akan ditambahkan ke detail produk yang dipilih.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tambah'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('{{ route('barang-masuk.existing') }}', $(this).serialize(), res => {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 1000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            window.location.href = '{{ route('barang-masuk.riwayat') }}';
                        }, 1000); // Redirect setelah 1 detik (sesuai timer alert)
                    }).fail(() => {
                        Swal.fire({
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan.',
                            icon: 'error',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                }
            })
        });
    </script>
@endpush

</script>
