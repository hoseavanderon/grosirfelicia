@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <section class="section">
        <div class="section-header">
            <h1>Riwayat Transaksi</h1>
        </div>

        {{-- FILTER FORM (JANGAN DI DALAM transaction-list) --}}
        <div class="mb-3 d-flex justify-content-center">
            <form id="filter-form" class="row w-100" style="max-width: 700px;">
                <div class="col-md-8 mb-2">
                    <input type="text" name="tanggal_riwayat" id="tanggal_riwayat" class="form-control text-center"
                        value="{{ old('tanggal_riwayat', $tanggalFilter) }}" autocomplete="off"
                        placeholder="Pilih rentang tanggal">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block w-100" type="submit">Cari</button>
                </div>
            </form>
        </div>

        {{-- DAFTAR TRANSAKSI AJAX --}}
        <div class="section-body" id="transaction-list">
            @include('partials.ajax-filter-riwayat', [
                'transactions' => $transactions,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalUangDiterima' => $totalUangDiterima,
            ])
        </div>
    </section>


    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const flatpickrInput = flatpickr("#tanggal_riwayat", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d F Y",
                    locale: {
                        firstDayOfWeek: 1,
                        weekdays: {
                            shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                            longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                        },
                        months: {
                            shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt',
                                'Nov', 'Des'
                            ],
                            longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                                'Agustus', 'September', 'Oktober', 'November', 'Desember'
                            ],
                        },
                    },
                    onClose: function(selectedDates, dateStr) {
                        if (dateStr) {
                            fetchFilteredTransactions(dateStr);
                        }
                    }
                });

                function fetchFilteredTransactions(dateStr) {
                    const url = `{{ route('riwayat-transaksi.ajax') }}?tanggal_riwayat=${encodeURIComponent(dateStr)}`;
                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('transaction-list').innerHTML = html;
                        })
                        .catch(err => {
                            console.error("Gagal mengambil data:", err);
                        });
                }

                document.getElementById('filter-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const dateStr = document.getElementById('tanggal_riwayat').value;
                    if (dateStr) {
                        fetchFilteredTransactions(dateStr);
                    }
                });
            });

            // 🟢 Pindahkan ke global scope
            function confirmDelete(event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Yakin ingin menghapus transaksi ini?',
                    text: "Data yang dihapus tidak bisa dikembalikan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Riwayat Berhasil Di Hapus',
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false
                        });

                        event.target.submit(); // 🟢 Submit jika konfirmasi
                    }
                });

                return false;
            }
        </script>
    @endpush
@endsection
