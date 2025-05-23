@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <section class="section">
        <div class="section-header">
            <h1>Laporan</h1>
        </div>

        <div class="mb-3 d-flex justify-content-center">
            <form id="form-laporan" class="row w-100" style="max-width: 700px;">
                <div class="col-md-8 mb-2">
                    <input type="text" name="tanggal_laporan" id="tanggal_laporan" class="form-control text-center"
                        value="{{ request('tanggal') }}" autocomplete="off" placeholder="Pilih rentang tanggal">
                </div>
            </form>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary">
                            <i class="far fa-file"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total Transaksi</h4>
                            </div>
                            <div class="card-body" id="total-transaksi">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-danger">
                            <i class="far fa-newspaper"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Penjualan</h4>
                            </div>
                            <div class="card-body" id="penjualan">Rp 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning">
                            <i class="far fa-user"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Jumlah Toko Order</h4>
                            </div>
                            <div class="card-body" id="jumlah-toko-order">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Jumlah Barang Terjual</h4>
                            </div>
                            <div class="card-body" id="jumlah-barang-terjual">0 pcs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            flatpickr("#tanggal_laporan", {
                mode: "range",
                dateFormat: "Y-m-d", // format untuk dikirim ke server (tetap)
                altInput: true,
                altFormat: "j F Y", // format yang ditampilkan di input
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                        longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    },
                    months: {
                        shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                            'September', 'Oktober', 'November', 'Desember'
                        ],
                    }
                },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        fetchLaporanData(dateStr);
                    }
                }
            });

            function fetchLaporanData(tanggal) {
                fetch('{{ route('laporan.data') }}?tanggal=' + encodeURIComponent(tanggal))
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        document.getElementById('total-transaksi').textContent = data.totalTransaksi;
                        document.getElementById('penjualan').textContent = 'Rp ' + new Intl.NumberFormat('id-ID')
                            .format(data.penjualan);
                        document.getElementById('jumlah-toko-order').textContent = data.jumlahTokoOrder;
                        document.getElementById('jumlah-barang-terjual').textContent = data.jumlahBarangTerjual + ' pcs';
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Gagal mengambil data laporan.');
                    });
            }
        </script>
    @endpush
@endsection
