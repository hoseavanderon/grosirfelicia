<?php

use App\Http\Controllers\BarangMasukController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KelolaProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\RiwayatTransaksiController;

Route::get('/', fn () => redirect()->route('login'));

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/products/filter', [HomeController::class, 'filter'])->name('products.filter');
    Route::get('/product/check-stock', [HomeController::class, 'checkStock']);
    Route::get('/customers/search', [HomeController::class, 'search']);
    Route::post('/checkout', [HomeController::class, 'checkout'])->name('checkout');

    Route::get('/riwayat-transaksi', [RiwayatTransaksiController::class, 'index'])->name('riwayat');
    Route::get('/riwayat-transaksi/ajax', [RiwayatTransaksiController::class, 'ajaxIndex'])->name('riwayat-transaksi.ajax');
    Route::get('/transactions/{transaction}/edit', [RiwayatTransaksiController::class, 'edit'])->name('riwayat-transaksi.edit');
    Route::put('/transactions/{transaction}', [RiwayatTransaksiController::class, 'update'])->name('riwayat-transaksi.update');
    Route::delete('/riwayat-transaksi/{id}', [RiwayatTransaksiController::class, 'destroy'])->name('riwayat-transaksi.destroy');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/data', [LaporanController::class, 'data'])->name('laporan.data');

    Route::get('/kelola-produk', [KelolaProdukController::class, 'index'])->name('kelola.produk.index');
    Route::get('/kelola-produk/daftar-produk', [KelolaProdukController::class, 'produk'])->name('kelola.produk.daftar');
    Route::get('/kelola-produk/kategori', [KelolaProdukController::class, 'kategori'])->name('kelola.produk.kategori');
    Route::get('/produk/wa-message', [KelolaProdukController::class, 'waMessage']);

    Route::get('/product/create', [KelolaProdukController::class, 'create'])->name('product.create');
    Route::post('/product', [KelolaProdukController::class, 'store'])->name('product.store');
    Route::get('/produk/search', [KelolaProdukController::class, 'search'])->name('produk.search');
    Route::get('/produk/load', [KelolaProdukController::class, 'load'])->name('produk.load');

    Route::get('/category/create', [KelolaProdukController::class, 'categoryCreate'])->name('category.create');
    Route::post('/category', [KelolaProdukController::class, 'categoryStore'])->name('category.store');
    
    Route::get('/langganan',[PelangganController::class, 'index'])->name('pelanggan');
    Route::get('/langganan/{id}', [PelangganController::class, 'show'])->name('pelanggan.show');
    Route::get('/langganan/ajax/{id}', [PelangganController::class, 'ajaxDetail']);
    Route::get('/kirim-wa/{id}', [PelangganController::class, 'kirimWhatsapp'])->name('kirim.whatsapp');
    Route::delete('/hapus-transaksi/{id}', [PelangganController::class, 'hapusTransaksi'])->name('hapus.transaksi');

    Route::get('/barang-masuk', [BarangMasukController::class, 'index'])->name('barang-masuk');
    Route::post('/barang-masuk/existing', [BarangMasukController::class, 'tambahKeProdukLama'])->name('barang-masuk.existing');
    Route::post('/barang-masuk/new', [BarangMasukController::class, 'buatProdukBaru'])->name('barang-masuk.new');
    Route::get('/barang-masuk/riwayat', [BarangMasukController::class, 'riwayat'])->name('barang-masuk.riwayat');
    Route::get('/barang-masuk/riwayat/{tanggal}', [BarangMasukController::class, 'getRiwayatByTanggal'])->name('barang-masuk.riwayat.tanggal');
    Route::delete('/barang-masuk/riwayat/{id}', [BarangMasukController::class, 'destroyRiwayat']);
});
