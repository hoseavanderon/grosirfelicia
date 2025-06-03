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
                                    <th>Expired</th>
                                    <th>Jumlah (pcs)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-control" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->nama_produk }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="date" name="items[0][expired]" class="form-control" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][pcs]" class="form-control" min="1"
                                            required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-remove">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="add-row" class="btn btn-secondary">+ Tambah Baris</button>
                        <button type="submit" class="btn btn-primary">Simpan Barang Masuk</button>
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

        let index = 1;
        $('#add-row').click(function() {
            $('#items-body').append(`
        <tr>
            <td>
                <select name="items[${index}][product_id]" class="form-control" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->nama_produk }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="date" name="items[${index}][expired]" class="form-control" required>
            </td>
            <td>
                <input type="number" name="items[${index}][pcs]" class="form-control" min="1" required>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-remove">Hapus</button>
            </td>
        </tr>
    `);
            index++;
        });

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
