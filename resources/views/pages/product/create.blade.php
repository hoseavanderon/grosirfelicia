@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header" style="display: flex; align-items: center;">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="pl-3">Tambah Produk</h1>
        </div>
        <div class="section-body">
            <form action="{{ route('product.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="category">Kategori</label>
                    <select name="category_id" id="category" class="form-control">
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="brand">Brand</label>
                    <select name="brand_id" id="brand" class="form-control">
                        <option value="">Pilih Brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="product_name">Nama Produk</label>
                    <input type="text" name="nama_produk" id="nama_produk" class="form-control" placeholder="Nama Produk"
                        required>
                </div>

                <div class="form-group">
                    <label for="price">Harga Jual Produk</label>
                    <input type="number" name="harga_jual" id="harga_jual" class="form-control" placeholder="Harga Jual"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Produk</button>
            </form>
        </div>
    </section>
@endsection
