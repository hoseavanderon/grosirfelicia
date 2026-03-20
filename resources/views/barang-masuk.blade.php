@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .card {
            border-radius: 12px !important;
        }

        .form-control {
            height: 42px !important;
        }

        .btn {
            border-radius: 8px !important;
        }
    </style>
    <section class="section">

        <div class="section-header d-flex justify-content-between align-items-center">
            <h1>Barang Masuk</h1>

            <div class="d-flex gap-2">
                <button type="button" id="add-row" class="btn btn-primary">
                    + Tambah Baris
                </button>
                <a href="{{ route('barang-masuk.riwayat') }}" class="btn btn-light border">
                    Riwayat Barang Masuk
                </a>
            </div>
        </div>

        <div class="section-body">
            <div class="tab-content">
                {{-- Tab Barang Sudah Ada --}}
                <div class="tab-pane fade show active" id="existing">
                    <form id="form-existing">
                        <table class="table align-middle">
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
                                        <select name="items[0][product_id]" class="form-control select2" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->nama_produk }}
                                                </option>
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
                                        <button type="button" class="btn btn-link text-danger btn-remove">
                                            🗑
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success px-4">
                                💾 Tambah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Produk dari Blade ke JavaScript
        const produkOptions = @json($products);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let index = 1;

        function generateProductOptions() {
            let options = '<option value="">-- Pilih Produk --</option>';
            produkOptions.forEach(product => {
                options += `<option value="${product.id}">${product.nama_produk}</option>`;
            });
            return options;
        }

        $('#add-row').click(function() {
            const options = generateProductOptions();
            const newRow = `
                <tr>
                    <td>
                        <select name="items[${index}][product_id]" class="form-control select2" required>
                            ${options}
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
            `;
            $('#items-body').append(newRow);
            index++;

            // Re-inisialisasi Select2 pada elemen baru
            initializeSelect2();
        });

        $(document).on('click', '.btn-remove', function() {
            $(this).closest('tr').remove();
        });

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
                        }, 1000);
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

        function initializeSelect2() {
            $('.select2').select2({
                placeholder: '-- Pilih Produk --',
                width: '100%',
                allowClear: true
            });
        }

        initializeSelect2();

        const FORM_KEY = 'barang_masuk_form_data';

        // ========== 1. Simpan ke localStorage saat input berubah ==========
        $(document).on('change input', '#form-existing :input', function() {
            const formData = $('#form-existing').serializeArray();
            localStorage.setItem(FORM_KEY, JSON.stringify(formData));
        });

        // ========== 2. Restore dari localStorage saat halaman dimuat ==========
        document.addEventListener('DOMContentLoaded', function() {
            const savedData = localStorage.getItem(FORM_KEY);
            if (savedData) {
                const data = JSON.parse(savedData);

                // Hitung berapa baris perlu ditambah
                const maxIndex = Math.max(...data.map(field => {
                    const match = field.name.match(/items\[(\d+)\]/);
                    return match ? parseInt(match[1]) : 0;
                }));

                for (let i = $('#items-body tr').length; i <= maxIndex; i++) {
                    $('#add-row').click(); // tambah baris sampai cukup
                }

                // Isi field dengan data yang disimpan
                data.forEach(field => {
                    const name = field.name;
                    const value = field.value;
                    const input = $(`[name="${name}"]`);
                    if (input.length) {
                        input.val(value).trigger('change');
                    }
                });
            }
        });

        // ========== 3. Peringatan sebelum keluar halaman ==========
        let isFormChanged = false;

        $(document).on('change input', '#form-existing :input', function() {
            isFormChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (isFormChanged) {
                e.preventDefault();
                e.returnValue = ''; // Chrome memerlukan nilai ini untuk menampilkan prompt
            }
        });

        // ========== 4. Bersihkan localStorage setelah submit sukses ==========
        $('#form-existing').on('submit', function() {
            localStorage.removeItem(FORM_KEY);
            isFormChanged = false;
        });
    </script>
@endpush

</script>
