@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Navigation Pills */
        .nav-pills .nav-link {
            margin-right: 5px;
            border-radius: .25rem;
            padding: .375rem .75rem;
            color: #007bff;
            border: 1px solid transparent;
            background-color: #f1f1f1;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        /* Responsive navigation pills */
        @media (max-width: 768px) {
            .nav-pills {
                flex-wrap: wrap;
                gap: 0.25rem;
            }

            .nav-pills .nav-link {
                margin-right: 0;
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
                flex: 1;
                text-align: center;
                min-width: calc(50% - 0.125rem);
            }
        }

        @media (max-width: 480px) {
            .nav-pills .nav-link {
                font-size: 0.7rem;
                padding: 0.4rem 0.5rem;
            }
        }

        /* Chart container with proper responsive behavior */
        .chart-container {
            position: relative;
            width: 100%;
            height: 450px;
            max-width: 100%;
        }

        @media (max-width: 1024px) {
            .chart-container {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .chart-container {
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .chart-container {
                height: 300px;
            }
        }

        .chart-container canvas {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
        }

        /* Statistics Cards - COMPLETELY REWRITTEN */
        .card-statistic-1 {
            background: white;
            border-radius: 12px;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
            overflow: hidden;
            height: auto;
        }

        .card-statistic-1:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .card-statistic-1 .card-wrap {
            padding: 1.5rem;
            position: relative;
            min-height: 90px;
        }

        .card-statistic-1 .card-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }

        .card-statistic-1 .card-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .card-statistic-1 .card-header {
            padding: 0;
            border: none;
            background: transparent;
            margin-bottom: 0.5rem;
        }

        .card-statistic-1 .card-header h4 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            margin: 0;
            line-height: 1.4;
            text-transform: none;
            letter-spacing: 0;
            max-width: calc(100% - 60px);
        }

        .card-statistic-1 .card-body {
            padding: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
            margin-top: 0.5rem;
            max-width: calc(100% - 60px);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Color variations */
        .card-statistic-1 .bg-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-statistic-1 .bg-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }

        .card-statistic-1 .bg-warning {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }

        .card-statistic-1 .bg-success {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        /* XL screens (1200px and up) - Keep default */

        /* LG screens (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .card-statistic-1 .card-wrap {
                padding: 1.2rem;
                min-height: 85px;
            }

            .card-statistic-1 .card-icon {
                width: 40px;
                height: 40px;
                top: 1.2rem;
                right: 1.2rem;
            }

            .card-statistic-1 .card-icon i {
                font-size: 1.2rem;
            }

            .card-statistic-1 .card-header h4 {
                font-size: 0.8rem;
                max-width: calc(100% - 50px);
            }

            .card-statistic-1 .card-body {
                font-size: 1.5rem;
                max-width: calc(100% - 50px);
            }
        }

        /* MD screens (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .card-statistic-1 {
                margin-bottom: 1rem;
            }

            .card-statistic-1 .card-wrap {
                padding: 1rem;
                min-height: 80px;
            }

            .card-statistic-1 .card-icon {
                width: 36px;
                height: 36px;
                top: 1rem;
                right: 1rem;
            }

            .card-statistic-1 .card-icon i {
                font-size: 1rem;
            }

            .card-statistic-1 .card-header h4 {
                font-size: 0.75rem;
                max-width: calc(100% - 46px);
            }

            .card-statistic-1 .card-body {
                font-size: 1.3rem;
                max-width: calc(100% - 46px);
            }
        }

        /* SM screens (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .card-statistic-1 {
                margin-bottom: 1rem;
            }

            .card-statistic-1 .card-wrap {
                padding: 1rem;
                text-align: center;
                min-height: 90px;
            }

            .card-statistic-1 .card-icon {
                position: static;
                margin: 0 auto 0.75rem auto;
                width: 36px;
                height: 36px;
            }

            .card-statistic-1 .card-header h4 {
                font-size: 0.8rem;
                max-width: 100%;
            }

            .card-statistic-1 .card-body {
                font-size: 1.4rem;
                max-width: 100%;
            }
        }

        /* XS screens (below 576px) */
        @media (max-width: 575.98px) {
            .card-statistic-1 {
                margin-bottom: 1rem;
            }

            .card-statistic-1 .card-wrap {
                padding: 0.875rem;
                text-align: center;
                min-height: 85px;
            }

            .card-statistic-1 .card-icon {
                position: static;
                margin: 0 auto 0.5rem auto;
                width: 32px;
                height: 32px;
            }

            .card-statistic-1 .card-icon i {
                font-size: 0.9rem;
            }

            .card-statistic-1 .card-header h4 {
                font-size: 0.75rem;
                max-width: 100%;
            }

            .card-statistic-1 .card-body {
                font-size: 1.2rem;
                max-width: 100%;
            }
        }

        /* Grid spacing - CLEANED UP */
        .row {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }

        .row>[class*="col-"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        @media (max-width: 575.98px) {
            .row {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }

            .row>[class*="col-"] {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }

        /* Form improvements */
        @media (max-width: 768px) {
            .section-header h1 {
                font-size: 1.5rem;
            }

            #form-laporan {
                max-width: 100% !important;
            }
        }

        /* Card header responsive behavior */
        .card-header {
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: stretch !important;
            }

            .card-header h4 {
                text-align: center;
                margin-bottom: 0.5rem;
            }
        }
    </style>
    <section class="section">
        <div class="section-header">
            <h1>Laporan</h1>
        </div>

        <div class="mb-3 d-flex justify-content-center">
            <form id="form-laporan" class="row w-100" style="max-width: 700px;">
                <div class="col-md-8 mb-2 mx-auto">
                    <input type="text" name="tanggal_laporan" id="tanggal_laporan" class="form-control text-center"
                        value="{{ request('tanggal') }}" autocomplete="off" placeholder="Pilih rentang tanggal">
                </div>
            </form>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary">
                            <i class="far fa-file"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header header_card">
                                <h4>Total Transaksi</h4>
                            </div>
                            <div class="card-body" id="total-transaksi">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-danger">
                            <i class="far fa-newspaper"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header header_card">
                                <h4>Penjualan</h4>
                            </div>
                            <div class="card-body" id="penjualan">Rp 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning">
                            <i class="far fa-user"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header header_card">
                                <h4>Jumlah Toko Order</h4>
                            </div>
                            <div class="card-body" id="jumlah-toko-order">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header header_card">
                                <h4>Jumlah Barang Terjual</h4>
                            </div>
                            <div class="card-body" id="jumlah-barang-terjual">0 pcs</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Grafik Penjualan Harian</h4>
                            <ul class="nav nav-pills d-flex" id="chartTab" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active"
                                        onclick="changeChartType('totalPenjualan', this)">Penjualan</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" onclick="changeChartType('jumlahBarang', this)">Barang
                                        Terjual</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link"
                                        onclick="changeChartType('jumlahTransaksi', this)">Transaksi</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" onclick="changeChartType('penjualanKategori', this)">Per
                                        Kategori</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartPenjualan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            flatpickr("#tanggal_laporan", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
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
                        fetchChartData(dateStr);
                    }
                }
            });

            function animateCounter(id, targetValue, prefix = '', suffix = '') {
                const el = document.getElementById(id);
                let start = 0;
                const duration = 1000;
                const increment = targetValue / (duration / 16);

                function update() {
                    start += increment;
                    if (start >= targetValue) {
                        el.textContent = prefix + new Intl.NumberFormat('id-ID').format(targetValue) + suffix;
                        return;
                    }
                    el.textContent = prefix + new Intl.NumberFormat('id-ID').format(Math.floor(start)) + suffix;
                    requestAnimationFrame(update);
                }

                update();
            }

            let currentType = 'totalPenjualan';
            let chartInstance = null;

            function changeChartType(type, btn) {
                currentType = type;
                const tanggal = document.getElementById('tanggal_laporan')?.value || '';
                fetchChartData(tanggal, type);

                document.querySelectorAll('#chartTab .nav-link').forEach(el => el.classList.remove('active'));
                btn.classList.add('active');
            }

            function getRandomColor() {
                const colors = [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ];
                return colors[Math.floor(Math.random() * colors.length)];
            }

            function getResponsiveConfig() {
                const isMobile = window.innerWidth <= 480;
                const isTablet = window.innerWidth <= 768;

                return {
                    maxTicksLimit: isMobile ? 3 : isTablet ? 5 : 8,
                    fontSize: isMobile ? 10 : isTablet ? 11 : 12,
                    pointRadius: isMobile ? 2 : 3,
                    pointHoverRadius: isMobile ? 4 : 5,
                    borderWidth: isMobile ? 2 : 3,
                    legendDisplay: !isMobile,
                    legendPosition: isMobile ? 'bottom' : 'top'
                };
            }

            function formatCurrency(value, isMobile = false) {
                if (isMobile && value >= 1000000) {
                    return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                } else if (isMobile && value >= 1000) {
                    return 'Rp ' + (value / 1000).toFixed(1) + 'K';
                }
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
            }

            function formatNumber(value, isMobile = false) {
                if (isMobile && value >= 1000) {
                    return (value / 1000).toFixed(1) + 'K';
                }
                return new Intl.NumberFormat('id-ID').format(value);
            }

            function fetchChartData(tanggal, type = 'totalPenjualan') {
                fetch(`{{ route('laporan.chartData') }}?tanggal=${encodeURIComponent(tanggal)}&type=${type}`)
                    .then(res => res.json())
                    .then(data => {
                        const canvas = document.getElementById('chartPenjualan');
                        const ctx = canvas.getContext('2d');
                        const config = getResponsiveConfig();
                        const isMobile = window.innerWidth <= 480;
                        const isTablet = window.innerWidth <= 768;

                        let labels = [];
                        let datasets = [];

                        if (type === 'penjualanKategori') {
                            const kategoriList = [...new Set(data.map(d => d.kategori))];
                            labels = [...new Set(data.map(d => d.tanggal))].sort();

                            // Format labels for mobile
                            if (isMobile) {
                                labels = labels.map(label => {
                                    const date = new Date(label);
                                    return date.getDate() + '/' + (date.getMonth() + 1);
                                });
                            }

                            datasets = kategoriList.map(kategori => ({
                                label: kategori,
                                data: labels.map((tgl, index) => {
                                    const originalDate = [...new Set(data.map(d => d.tanggal))].sort()[
                                        index];
                                    const found = data.find(d => d.kategori === kategori && d
                                        .tanggal === originalDate);
                                    return found ? found.total_terjual : 0;
                                }),
                                borderColor: getRandomColor(),
                                backgroundColor: 'transparent',
                                fill: false,
                                tension: 0.4,
                                borderWidth: config.borderWidth,
                                pointRadius: config.pointRadius,
                                pointHoverRadius: config.pointHoverRadius
                            }));
                        } else {
                            labels = data.map(d => {
                                const date = new Date(d.tanggal);
                                if (isMobile) {
                                    return date.getDate() + '/' + (date.getMonth() + 1);
                                } else if (isTablet) {
                                    return date.toLocaleDateString('id-ID', {
                                        day: 'numeric',
                                        month: 'short'
                                    });
                                }
                                return date.toLocaleDateString('id-ID');
                            });

                            let label = '',
                                key = '',
                                color = 'rgba(54, 162, 235, 1)';

                            if (type === 'totalPenjualan') {
                                label = 'Total Penjualan (Rp)';
                                key = 'penjualan';
                                color = 'rgba(54, 162, 235, 1)';
                            } else if (type === 'jumlahBarang') {
                                label = 'Jumlah Barang Terjual';
                                key = 'total_barang';
                                color = 'rgba(75, 192, 192, 1)';
                            } else if (type === 'jumlahTransaksi') {
                                label = 'Jumlah Transaksi';
                                key = 'total_transaksi';
                                color = 'rgba(255, 205, 86, 1)';
                            }

                            datasets = [{
                                label,
                                data: data.map(d => d[key]),
                                borderColor: color,
                                backgroundColor: color.replace('1)', '0.1)'),
                                fill: true,
                                tension: 0.4,
                                borderWidth: config.borderWidth,
                                pointRadius: config.pointRadius,
                                pointHoverRadius: config.pointHoverRadius
                            }];
                        }

                        if (chartInstance) chartInstance.destroy();

                        chartInstance = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels,
                                datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                plugins: {
                                    legend: {
                                        display: config.legendDisplay || type === 'penjualanKategori',
                                        position: config.legendPosition,
                                        labels: {
                                            usePointStyle: true,
                                            padding: isMobile ? 10 : 20,
                                            font: {
                                                size: config.fontSize
                                            }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: 'white',
                                        bodyColor: 'white',
                                        cornerRadius: 8,
                                        displayColors: true,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.dataset.label || '';
                                                const value = context.parsed.y;

                                                if (type === 'totalPenjualan') {
                                                    return `${label}: ${formatCurrency(value, isMobile)}`;
                                                }
                                                return `${label}: ${formatNumber(value, isMobile)}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            autoSkip: true,
                                            maxTicksLimit: config.maxTicksLimit,
                                            maxRotation: isMobile ? 45 : 0,
                                            minRotation: 0,
                                            font: {
                                                size: config.fontSize
                                            },
                                            color: '#6B7280'
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            font: {
                                                size: config.fontSize
                                            },
                                            color: '#6B7280',
                                            callback: function(value) {
                                                if (type === 'totalPenjualan') {
                                                    return formatCurrency(value, isMobile);
                                                }
                                                return formatNumber(value, isMobile);
                                            }
                                        }
                                    }
                                },
                                elements: {
                                    point: {
                                        hoverBackgroundColor: 'white',
                                        hoverBorderWidth: 2
                                    }
                                }
                            }
                        });
                    })
                    .catch(err => console.error('Gagal ambil data grafik:', err));
            }

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

                        animateCounter('total-transaksi', data.totalTransaksi);
                        animateCounter('penjualan', data.penjualan, 'Rp ');
                        animateCounter('jumlah-toko-order', data.jumlahTokoOrder);
                        animateCounter('jumlah-barang-terjual', data.jumlahBarangTerjual, '', ' pcs');
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Gagal mengambil data laporan.');
                    });
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                if (chartInstance) {
                    const tanggal = document.getElementById('tanggal_laporan')?.value || '';
                    setTimeout(() => {
                        fetchChartData(tanggal, currentType);
                    }, 100);
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const tanggal = document.getElementById('tanggal_laporan')?.value || '';
                fetchChartData(tanggal, currentType);
            });
        </script>
    @endpush
@endsection
