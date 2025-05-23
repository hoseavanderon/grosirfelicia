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
                <div class="input-group">
                    <input type="text" name="cari" value="{{ request('cari') }}" class="form-control"
                        placeholder="Cari produk...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>

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
