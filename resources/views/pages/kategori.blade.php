@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header" style="display: flex; align-items: center;">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="pl-3">Daftar Kategori</h1>
        </div>
        <div class="section-body">
            <ul class="list-group">
                @foreach ($categories as $category)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $category->category }}
                        <span>( {{ $category->products->count() }} )</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

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

    <a href="{{ route('category.create') }}" class="btn btn-primary rounded-circle"
        style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; font-size: 24px;">
        +
    </a>
@endsection
