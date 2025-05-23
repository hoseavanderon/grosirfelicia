@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .card-statistic-1 {
            margin-bottom: 1rem;
        }

        .card-body {
            font-size: 1.25rem;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .card-wrap {
                text-align: center;
            }
        }
    </style>
@endpush


@php
    $totalPenjualan = $customer->transactions->sum('subtotal');
    $totalOrder = $customer->transactions->count();
    $totalItem = $customer->transactions->sum('jumlah_item');
    $largestSubtotal = $customer->transactions->max('subtotal');
@endphp

@section('content')
    <section class="section">
        <div class="section-header" style="display: flex; align-items: center;">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="pl-3 text-uppercase">{{ $customer->nama_pelanggan }}</h1>
        </div>

        <div class="section-body">
            <div class="mb-3 d-flex justify-content-center">
                <form class="row w-100" style="max-width: 700px;" method="GET" action="">
                    <div class="col-md-8 mb-2">
                        <input type="text" name="tanggal_transaksi" id="tanggal_transaksi"
                            class="form-control text-center" value="{{ request('tanggal_transaksi') }}" autocomplete="off"
                            placeholder="Pilih rentang tanggal">
                    </div>
                </form>
            </div>

            <div id="result-area">
                @include('partials.detail-transaksi', ['customer' => $customer])
            </div>

            <div id="loading" style="display:none; text-align:center; margin: 1rem;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#tanggal_transaksi", {
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
            onClose: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const id = {{ $customer->id }};
                    const url = `/langganan/ajax/${id}?tanggal_transaksi=${encodeURIComponent(dateStr)}`;

                    document.getElementById('loading').style.display = 'block';

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            document.querySelector('#result-area').innerHTML = data.html;
                        });
                }
            }
        });
    </script>
@endpush
