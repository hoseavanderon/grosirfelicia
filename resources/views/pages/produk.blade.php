@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header" style="display: flex; align-items: center;">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="pl-3">Daftar Produk</h1>
        </div>

        <div class="section-body">
            {{-- Form Cari --}}
            <form action="" method="GET" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" name="cari" value="{{ request('cari') }}" class="form-control"
                            placeholder="Cari produk...">
                    </div>

                    <input type="date" name="last_report_date" id="last_report_date"
                        value="{{ request('last_report_date', \Carbon\Carbon::now()->subDay()->format('Y-m-d')) }}"
                        class="form-control form-control-sm d-inline-block" style="max-width: 200px;">
                </div>
            </form>

            @php
                use Carbon\Carbon;

                $lastReportDate = request('last_report_date')
                    ? Carbon::parse(request('last_report_date'))->startOfDay()
                    : Carbon::now()->subDay()->startOfDay();

                use Illuminate\Database\Eloquent\Builder;

                $produkBerubah = \App\Models\Product::with(['brand', 'detailProducts'])
                    ->whereHas('detailProducts.detailTransactions', function (Builder $query) use ($lastReportDate) {
                        $query->whereDate('created_at', '=', $lastReportDate->toDateString());
                    })
                    ->get();

                $allProducts = \App\Models\Product::with(['brand', 'detailProducts.detailTransactions'])->get();

                $waMessage = Carbon::now()->format('d F Y') . "\n\n";

                $grouped = $allProducts->groupBy(fn($product) => $product->brand->brand ?? 'Tanpa Brand');

                foreach ($grouped as $brandName => $productsByBrand) {
                    $waMessage .= strtoupper($brandName) . "\n";

                    foreach ($productsByBrand as $product) {
                        $totalStok = $product->detailProducts->sum('stok');

                        $hasTransaction = $product->detailProducts
                            ->flatMap(fn($dp) => $dp->detailTransactions)
                            ->contains(fn($dt) => $dt->created_at->toDateString() == $lastReportDate->toDateString());

                        $label = $hasTransaction ? '' : ' ok';

                        $desc = trim($product->nama_produk);

                        $waMessage .= "{$desc} {$totalStok} pcs{$label}\n";
                    }

                    $waMessage .= "\n";
                }
            @endphp

            <textarea id="waMessageText" class="d-none">{{ $waMessage }}</textarea>
            <div id="wa-output" class="d-none">{{ $waMessage }}</div>

            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const dateInput = document.getElementById('last_report_date');
                    const waOutputDiv = document.getElementById('wa-output');

                    dateInput.addEventListener('change', function() {
                        const selectedDate = this.value;

                        fetch(`?last_report_date=${selectedDate}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.text())
                            .then(html => {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = html;

                                const newMessage = tempDiv.querySelector('#wa-output')?.textContent || '';

                                document.getElementById('waMessageText').value = newMessage;
                                waOutputDiv.textContent = newMessage;

                                // Salin otomatis ke clipboard
                                navigator.clipboard.writeText(newMessage).then(() => {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Pesan disalin!',
                                        text: `Data berdasarkan ${selectedDate} telah disalin ke clipboard.`,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                });
                            });
                    });
                });

                function copyWA() {
                    const textArea = document.getElementById('waMessageText');
                    navigator.clipboard.writeText(textArea.value).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Disalin!',
                            text: 'Pesan berhasil disalin ke clipboard.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    });
                }
            </script>

            <ul class="list-group mb-5" id="product-list">

            </ul>

            <div class="text-center" id="loader" style="display:none;">
                <p>Loading...</p>
            </div>
        </div>
    </section>

    <a href="{{ route('product.create') }}" class="btn btn-primary rounded-circle"
        style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; font-size: 24px;">
        +
    </a>

    @if (session('success'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif

    @push('scripts')
        <script>
            const input = document.querySelector('input[name="cari"]');
            input.addEventListener('input', function() {
                fetch(`/produk/search?cari=${this.value}`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('product-list').innerHTML = data.html;
                    });
            });
        </script>

        <script>
            let nextPageUrl = '/produk/load'; // first load
            let isLoading = false;
            let cari = '';

            const loadProducts = () => {
                if (isLoading || !nextPageUrl) return;
                isLoading = true;
                document.getElementById('loader').style.display = 'block';

                fetch(nextPageUrl + (cari ? '&cari=' + cari : ''))
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('product-list').insertAdjacentHTML('beforeend', data.html);
                        nextPageUrl = data.next_page_url;
                        isLoading = false;
                        document.getElementById('loader').style.display = 'none';
                    });
            };

            // Auto load saat pertama kali
            window.addEventListener('DOMContentLoaded', loadProducts);

            // Scroll detector
            window.addEventListener('scroll', () => {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
                    loadProducts();
                }
            });

            // Live search
            document.querySelector('input[name="cari"]').addEventListener('input', function() {
                cari = this.value;
                nextPageUrl = '/produk/load?cari=' + cari;
                document.getElementById('product-list').innerHTML = '';
                loadProducts();
            });
        </script>
    @endpush
@endsection
