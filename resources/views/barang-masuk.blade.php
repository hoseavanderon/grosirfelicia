@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f5f7fb;
        }

        .section-header h1 {
            font-size: 18px;
            font-weight: 600;
        }

        .btn-primary {
            background: #4f46e5;
            border: none;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 14px;
        }

        .btn-light {
            background: #eef2ff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            color: #3730a3;
        }

        .btn-success {
            background: #22c55e;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
        }

        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            height: 42px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .btn-remove {
            font-size: 16px;
        }

        .select2-container {
            width: 100% !important;
        }

        .btn svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .btn.d-flex {
            display: inline-flex !important;
            align-items: center;
        }

        .btn .gap-1 {
            gap: 6px;
        }

        .btn-soft-primary {
            background: #6366f1 !important;
            color: #fff !important;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 14px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-soft-primary:hover,
        .btn-soft-primary:focus {
            background: #4f46e5 !important;
            color: #fff !important;
        }

        .btn-soft-primary:active {
            transform: scale(0.97);
        }

        .btn-soft {
            background: #f1f5f9;
            color: #334155;
            transition: all 0.2s ease;
        }

        .btn-soft:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-remove {
            color: #ef4444;
            background: transparent;
            border: none;
        }

        .btn-remove:hover {
            color: #dc2626;
            background: transparent;
        }

        .swal2-container {
            z-index: 99999 !important;
        }
    </style>
    <script src="https://unpkg.com/heroicons@2.0.18/dist/heroicons.min.js"></script>
    <section class="section">

        <div class="section-header d-flex justify-content-between align-items-center">
            <h1>Barang Masuk</h1>

            <div class="d-flex gap-2">
                <button type="button" id="add-row" class="btn btn-soft-primary d-inline-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    <span class="ms-1">Tambah</span>
                </button>

                <a href="{{ route('barang-masuk.riwayat') }}" class="btn btn-soft d-inline-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                    <span class="ms-1">Riwayat</span>
                </a>
            </div>
        </div>

        <div class="section-body">
            <div class="tab-content">
                {{-- Tab Barang Sudah Ada --}}
                <div class="tab-pane fade show active" id="existing">
                    <form id="form-existing">
                        {{-- DESKTOP TABLE --}}
                        <div class="d-none d-md-block">
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
                                            <select name="items[0][product_id]" class="form-control select2 w-100" required>
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
                                            <button type="button" class="btn btn-link text-danger btn-remove">🗑</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- MOBILE CARD --}}
                        <div class="d-md-none" id="items-body-mobile">
                            <div class="card mb-3 p-3 shadow-sm">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">Item 1</span>
                                    <button type="button" class="btn btn-link text-danger btn-remove">🗑</button>
                                </div>

                                <div class="mb-2">
                                    <label>Produk</label>
                                    <select name="items[0][product_id]" class="form-control select2 w-100" required>
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->nama_produk }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label>Tanggal Kadaluarsa</label>
                                    <input type="date" name="items[0][expired]" class="form-control" required>
                                </div>

                                <div>
                                    <label>Jumlah (pcs)</label>
                                    <input type="number" name="items[0][pcs]" class="form-control" min="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success w-100">
                                Simpan Barang Masuk
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
        // 🔥 HANDLE MOBILE vs DESKTOP (INI KUNCI)
        function toggleFormByScreen() {
            if (window.innerWidth >= 768) {

                // 🔥 sync dulu sebelum disable
                syncDataToDesktop();

                $('#items-body-mobile :input').prop('disabled', true);
                $('#items-body :input').prop('disabled', false);

            } else {

                // 🔥 sync dulu sebelum disable
                syncDataToMobile();

                $('#items-body :input').prop('disabled', true);
                $('#items-body-mobile :input').prop('disabled', false);
            }
        }

        function syncDataToMobile() {
            $('#items-body tr').each(function(i) {
                const product = $(this).find('[name*="[product_id]"]').val();
                const expired = $(this).find('[name*="[expired]"]').val();
                const pcs = $(this).find('[name*="[pcs]"]').val();

                const mobileCard = $('#items-body-mobile .card').eq(i);

                if (mobileCard.length) {
                    if (product) {
                        mobileCard.find('[name*="[product_id]"]').val(product).trigger('change');
                    }

                    if (expired) {
                        mobileCard.find('[name*="[expired]"]').val(expired);
                    }

                    if (pcs) {
                        mobileCard.find('[name*="[pcs]"]').val(pcs);
                    }
                }
            });
        }

        function syncDataToDesktop() {
            $('#items-body-mobile .card').each(function(i) {
                const product = $(this).find('[name*="[product_id]"]').val();
                const expired = $(this).find('[name*="[expired]"]').val();
                const pcs = $(this).find('[name*="[pcs]"]').val();

                const row = $('#items-body tr').eq(i);

                if (row.length) {
                    row.find('[name*="[product_id]"]').val(product).trigger('change');
                    row.find('[name*="[expired]"]').val(expired);
                    row.find('[name*="[pcs]"]').val(pcs);
                }
            });
        }

        // run pertama
        toggleFormByScreen();

        // run saat resize
        $(window).on('resize', toggleFormByScreen);
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
                        <select name="items[${index}][product_id]" class="form-control select2 w-100" required>
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
                         <button type="button" class="btn btn-link text-danger btn-remove p-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h12" />
                                <path d="M8 6V4h4v2" />
                                <path d="M6 6v10a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" />
                                <path d="M9 10v6M11 10v6" />
                            </svg>
                        </button>
                    </td>
                </tr>
            `;

            // MOBILE VERSION
            const newMobile = `
            <div class="card mb-3 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Item ${index + 1}</strong>
                    <button type="button" class="btn btn-link text-danger btn-remove">🗑</button>
                </div>

                <div class="mb-2">
                    <label>Produk</label>
                    <select name="items[${index}][product_id]" class="form-control select2" required>
                        ${options}
                    </select>
                </div>

                <div class="mb-2">
                    <label>Tanggal Kadaluarsa</label>
                    <input type="date" name="items[${index}][expired]" class="form-control" required>
                </div>

                <div>
                    <label>Jumlah (pcs)</label>
                    <input type="number" name="items[${index}][pcs]" class="form-control" min="1" required>
                </div>
            </div>
            `;

            $('#items-body').append(newRow);
            $('#items-body-mobile').append(newMobile);
            index++;

            // Re-inisialisasi Select2 pada elemen baru
            initializeSelect2();
            toggleFormByScreen();
        });

        $(document).on('click', '.btn-remove', function() {
            $(this).closest('tr, .card').remove();
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

        $('#form-existing').on('submit', function() {
            localStorage.removeItem(FORM_KEY);
            isFormChanged = false;
        });
    </script>
@endpush

</script>
