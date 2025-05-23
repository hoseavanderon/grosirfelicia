@extends('layouts.app')

@section('content')
    <div class="section">
        <div class="section-header">
            <h3>Edit Transaksi</h3>
        </div>

        <div class="mb-3">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        <form id="edit-transaction-form" action="{{ route('riwayat-transaksi.update', $transaction->id) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="deleted_ids[]" id="deleted-ids">
            <div class="card shadow p-3 mb-4">
                <h5 class="mb-3">Data Pelanggan</h5>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label>Nama Pelanggan</label>
                        <input type="text" class="form-control" value="{{ $transaction->customer->nama_pelanggan }}"
                            readonly>
                    </div>
                    <div class="col-md-6">
                        <label>No Telp</label>
                        <input type="text" class="form-control" value="{{ $transaction->customer->no_telp }}" readonly>
                    </div>
                </div>
            </div>

            <div class="card shadow p-3 mb-4">
                <h5 class="mb-3">Detail Produk</h5>
                <div id="product-items">
                    @foreach ($transaction->detailTransactions as $i => $item)
                        <div class="border rounded p-3 mb-3 product-item bg-light">
                            <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $i }}][detail_product_id]"
                                value="{{ $item->detail_product_id }}">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label>Produk</label>
                                    <input type="text" class="form-control"
                                        value="{{ $item->detailProduct->product->nama_produk }} (Exp: {{ \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m/Y') }})"
                                        readonly>
                                </div>
                                <div class="col-md-2">
                                    <label>Jumlah (pcs)</label>
                                    <input type="number" name="items[{{ $i }}][pcs]" class="form-control"
                                        value="{{ $item->pcs }}" min="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Harga Jual</label>
                                    <input type="number" name="items[{{ $i }}][harga_jual]"
                                        class="form-control" value="{{ $item->harga_jual }}" min="0" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-block remove-item">Hapus</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row mt-3">
                    <div class="col-6">
                        <h6>Total Pcs: <span id="total-pcs">0</span></h6>
                    </div>
                    <div class="col-6 text-end">
                        <h6>Subtotal: Rp <span id="subtotal">0</span></h6>
                    </div>
                </div>
            </div>

            <div class="card shadow p-3 mb-4">
                <h5 class="mb-3">Tambah Produk</h5>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label>Produk</label>
                        <select class="form-control" id="new-product">
                            <option value="" disabled selected>Pilih produk</option>
                            @foreach ($detailProducts as $dp)
                                <option value="{{ $dp->id }}" data-nama="{{ $dp->product->nama_produk }}"
                                    data-expired="{{ \Carbon\Carbon::parse($dp->expired)->format('d/m/Y') }}"
                                    data-harga="{{ $dp->product->harga_jual }}">
                                    {{ $dp->product->nama_produk }} (Exp:
                                    {{ \Carbon\Carbon::parse($dp->expired)->format('d/m/Y') }}, Stok: {{ $dp->stok }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Jumlah (pcs)</label>
                        <input type="number" id="new-pcs" class="form-control" min="1">
                    </div>
                    <div class="col-md-3">
                        <label>Harga Jual</label>
                        <input type="number" id="new-harga" class="form-control" min="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success btn-block" id="add-product">Tambah</button>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('riwayat') }}" class="btn btn-secondary">Batal</a>
                <button type="button" class="btn btn-primary" id="confirm-submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let itemIndex = document.querySelectorAll('.product-item').length;

        // Fungsi untuk update total pcs & subtotal
        function updateSummary() {
            let totalPcs = 0;
            let subtotal = 0;

            document.querySelectorAll('.product-item').forEach(item => {
                const pcs = parseInt(item.querySelector('input[name$="[pcs]"]')?.value || 0);
                const harga = parseInt(item.querySelector('input[name$="[harga_jual]"]')?.value || 0);
                totalPcs += pcs;
                subtotal += pcs * harga;
            });

            document.getElementById('total-pcs').textContent = totalPcs;
            document.getElementById('subtotal').textContent = subtotal.toLocaleString('id-ID');
        }

        document.getElementById('new-product').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const harga = selected.getAttribute('data-harga');
            document.getElementById('new-harga').value = harga || '';
        });

        document.getElementById('add-product').addEventListener('click', function() {
            const select = document.getElementById('new-product');
            const pcsInput = document.getElementById('new-pcs');
            const hargaInput = document.getElementById('new-harga');

            const detailProductId = select.value;
            const namaProduk = select.options[select.selectedIndex]?.getAttribute('data-nama');
            const expired = select.options[select.selectedIndex]?.getAttribute('data-expired');
            const pcs = parseInt(pcsInput.value);
            const harga = parseInt(hargaInput.value);

            if (!detailProductId || !pcs || !harga) {
                alert('Lengkapi semua kolom terlebih dahulu!');
                return;
            }

            let existingItem = null;
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach(item => {
                const input = item.querySelector('input[name$="[detail_product_id]"]');
                if (input && input.value == detailProductId) {
                    existingItem = item;
                }
            });

            if (existingItem) {
                const pcsField = existingItem.querySelector('input[name$="[pcs]"]');
                pcsField.value = parseInt(pcsField.value) + pcs;
                updateSummary();
            } else {
                const html = `
        <div class="product-item border rounded p-3 mb-3 bg-light">
            <input type="hidden" name="items[${itemIndex}][detail_product_id]" value="${detailProductId}">
            <div class="row g-2">
                <div class="col-md-5">
                    <label>Produk</label>
                    <input type="text" class="form-control" value="${namaProduk} (Exp: ${expired})" readonly>
                </div>
                <div class="col-md-2">
                    <label>Jumlah (pcs)</label>
                    <input type="number" name="items[${itemIndex}][pcs]" class="form-control" value="${pcs}" min="1" required>
                </div>
                <div class="col-md-3">
                    <label>Harga Jual</label>
                    <input type="number" name="items[${itemIndex}][harga_jual]" class="form-control" value="${harga}" min="0" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-block remove-item">Hapus</button>
                </div>
            </div>
        </div>
        `;
                document.getElementById('product-items').insertAdjacentHTML('beforeend', html);
                itemIndex++;
            }

            // Reset form input bawah
            select.selectedIndex = 0;
            pcsInput.value = '';
            hargaInput.value = '';

            updateSummary();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.preventDefault();
                Swal.fire({
                    title: 'Yakin ingin menghapus produk ini?',
                    text: "Data yang dihapus tidak bisa dikembalikan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const itemDiv = e.target.closest('.product-item');

                        // Ambil ID detail jika ada
                        const idInput = itemDiv.querySelector('input[name$="[id]"]');
                        if (idInput) {
                            const deletedInput = document.createElement('input');
                            deletedInput.type = 'hidden';
                            deletedInput.name = 'deleted_ids[]';
                            deletedInput.value = idInput.value;
                            document.getElementById('edit-transaction-form').appendChild(deletedInput);
                        }

                        itemDiv.remove();
                        updateSummary();
                    }
                });
            }
        });

        document.addEventListener('input', function(e) {
            if (
                e.target.name?.includes('[pcs]') ||
                e.target.name?.includes('[harga_jual]')
            ) {
                updateSummary();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            updateSummary();
        });

        document.getElementById('confirm-submit').addEventListener('click', function() {
            Swal.fire({
                title: 'Simpan Perubahan?',
                text: "Pastikan semua data sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('edit-transaction-form').submit();
                }
            });
        });
    </script>
@endpush
