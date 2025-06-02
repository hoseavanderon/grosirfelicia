@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Kelola Produk</h1>
        </div>

        <div class="section-body">
            <div class="list-group mb-4">
                <a href="{{ route('kelola.produk.daftar') }}"
                    class="list-group-item d-flex justify-content-between align-items-center text-dark">
                    <span>Daftar Produk</span>
                    <div class="d-flex align-items-center">
                        <span class="mr-2">{{ number_format($totalProduct) }} pcs</span>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <a href="{{ route('kelola.produk.kategori') }}"
                    class="list-group-item d-flex justify-content-between align-items-center text-dark">
                    <span>Kategori</span>
                    <div class="d-flex align-items-center">
                        <span class="mr-2">{{ number_format($totalCategory) }} Kategori</span>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>

            <div class="row">
                <!-- Card: Barang Terlaris Tahun Ini -->
                <div class="col-md-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Barang Terlaris Tahun Ini</h5>
                        </div>
                        <div class="card-body p-0">
                            @if ($bestSellers->isEmpty())
                                <p class="p-3 mb-0">Belum ada data penjualan tahun ini.</p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach ($bestSellers as $item)
                                        @php
                                            $product = $bestSellingProducts[$item->product_id] ?? null;
                                        @endphp
                                        @if ($product)
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center text-uppercase">
                                                {{ $product->nama_produk }}
                                                <span class="badge badge-success badge-pill">
                                                    {{ number_format($item->total_terjual) }} pcs
                                                </span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- Card: Produk Mau Expired -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Barang yang akan expired dalam 1 bulan</h5>
                            @php
                                $waMessage = '';

                                if ($expiringSoon->isEmpty()) {
                                    $waMessage = 'Sementara belum ada produk yang expired dalam waktu 1 bulan.';
                                } else {
                                    $waMessage = "Daftar produk yang akan expired dalam 1 bulan:\n";
                                    foreach ($expiringSoon as $product) {
                                        foreach ($product->detailProducts as $detail) {
                                            $waMessage .=
                                                "- {$product->nama_produk} ({$detail->stok} pcs), exp: " .
                                                \Carbon\Carbon::parse($detail->expired)->format('d M Y') .
                                                "\n";
                                        }
                                    }
                                }

                                $waUrl = 'https://wa.me/6282247501842?text=' . urlencode($waMessage);
                            @endphp

                            <a href="{{ $waUrl }}" target="_blank" class="btn btn-success btn-sm">
                                <i class="fab fa-whatsapp"></i> Kirim WA
                            </a>
                        </div>
                        <div class="card-body p-0">
                            @if ($expiringSoon->isEmpty())
                                <p class="p-3 mb-0 text-center">Tidak ada barang yang akan expired dalam waktu dekat.</p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach ($expiringSoon as $product)
                                        @foreach ($product->detailProducts as $detail)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $product->nama_produk }} - {{ $detail->stok }} Pcs
                                                <span
                                                    class="badge badge-warning badge-pill">{{ \Carbon\Carbon::parse($detail->expired)->translatedFormat('d
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                F Y') }}</span>
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card: Produk Stok Sedikit -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        @php
                            $message = "Daftar barang dengan stok kurang dari 30 pcs:\n";

                            foreach ($lowStock as $product) {
                                $message .= "- {$product->nama_produk}: {$product->detail_products_sum_stok} pcs\n";
                            }

                            $whatsappUrl = 'https://wa.me/6282247501842?text=' . urlencode($message);
                        @endphp

                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Barang dengan stok sisa kurang dari 30 pcs</h5>
                            <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-success btn-sm">
                                <i class="fab fa-whatsapp"></i> Kirim WA
                            </a>
                        </div>

                        <div class="card-body p-0">
                            @if ($lowStock->isEmpty())
                                <p class="p-3 mb-0">Tidak ada barang dengan stok yang rendah.</p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach ($lowStock as $product)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $product->nama_produk }}
                                            <span class="badge badge-danger badge-pill">
                                                {{ $product->detail_products_sum_stok }} pcs
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
