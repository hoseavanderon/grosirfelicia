<?php

use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\CekStokController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JejakProdukController;
use App\Http\Controllers\LanggananController;
use App\Http\Controllers\RiwayatTransaksiController;
use App\Http\Controllers\KelolaProdukController;
use App\Http\Controllers\LaporanTransaksiController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home', [HomeController::class, 'index'])
        ->name('home');
    Route::post('/products/reorder', [HomeController::class, 'reorder']
        )->name('products.reorder');

    Route::get('/customers/search', [CustomerController::class, 'search'])
        ->name('customers.search');

    Route::post('/transactions', [TransactionController::class, 'store'])
        ->name('transactions.store');

    Route::get('/transactions/detail-products', [TransactionController::class, 'detailProducts'])
        ->name('transactions.detail-products');

    Route::put('/transactions/{id}', [TransactionController::class, 'update'])
        ->whereNumber('id')
        ->name('transactions.update');

    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])
        ->whereNumber('id')
        ->name('transactions.destroy');

    Route::get('/riwayat-transaksi', [RiwayatTransaksiController::class, 'index'])
        ->name('riwayat.transaksi');

    Route::get('/transactions/list', [RiwayatTransaksiController::class, 'list'])
        ->name('transactions.list');

    Route::patch('/transactions/deposit-by-date', [RiwayatTransaksiController::class, 'depositByDate'])
        ->name('transactions.deposit.by-date');

    Route::patch('/transactions/{id}/payment', [RiwayatTransaksiController::class, 'updatePayment'])
        ->whereNumber('id')
        ->name('transactions.payment.update');

    Route::get('/barang-masuk', [BarangMasukController::class, 'index'])
        ->name('barang.masuk');

    Route::get('/barang-masuk/history', [BarangMasukController::class, 'history'])
        ->name('barang.masuk.history');

    Route::get('/barang-masuk/products', [BarangMasukController::class, 'products'])
        ->name('barang.masuk.products');

    Route::post('/barang-masuk/draft', [BarangMasukController::class, 'saveDraft'])
        ->name('barang.masuk.draft.save');

    Route::delete('/barang-masuk/draft', [BarangMasukController::class, 'discardDraft'])
        ->name('barang.masuk.draft.discard');

    Route::post('/barang-masuk', [BarangMasukController::class, 'store'])
        ->name('barang.masuk.store');

    Route::get('/barang-masuk/history/list', [BarangMasukController::class, 'historyList'])
        ->name('barang.masuk.history.list');

    Route::get('/barang-masuk/history/{date}', [BarangMasukController::class, 'showByDate'])
        ->name('barang.masuk.history.show');

    Route::delete('/barang-masuk/logs/{id}', [BarangMasukController::class, 'destroyLog'])
        ->whereNumber('id')
        ->name('barang.masuk.logs.destroy');

    Route::get('/langganan', [LanggananController::class, 'index'])
        ->name('langganan');

    Route::get('/customers/{id}', [LanggananController::class, 'show'])
        ->whereNumber('id')
        ->name('customers.show');

    Route::get('/customers/{id}/data', [LanggananController::class, 'data'])
        ->whereNumber('id')
        ->name('customers.data');

    Route::get('/produk', [KelolaProdukController::class, 'index'])
        ->name('produk');

    Route::get('/produk/analytics/best-sellers', [KelolaProdukController::class, 'bestSellers'])
        ->name('produk.analytics.best-sellers');

    Route::get('/produk/analytics/expiring', [KelolaProdukController::class, 'expiringSoon'])
        ->name('produk.analytics.expiring');

    Route::get('/produk/analytics/critical', [KelolaProdukController::class, 'criticalStock'])
        ->name('produk.analytics.critical');

    Route::get('/stok', [CekStokController::class, 'index'])
        ->name('stok');

    Route::get('/stok/data', [CekStokController::class, 'data'])
        ->name('stok.data');

    Route::post('/stok/save', [CekStokController::class, 'save'])
        ->name('stok.save');

    Route::get('/jejak-produk', [JejakProdukController::class, 'index'])
        ->name('jejak.produk');

    Route::get('/jejak-produk/products', [JejakProdukController::class, 'products'])
        ->name('jejak.produk.products');

    Route::get('/jejak-produk/data', [JejakProdukController::class, 'data'])
        ->name('jejak.produk.data');

    Route::get('/laporan-transaksi', [LaporanTransaksiController::class, 'index'])
        ->name('laporan.transaksi');

    Route::get('/laporan-transaksi/data', [LaporanTransaksiController::class, 'data'])
        ->name('laporan.transaksi.data');
});
