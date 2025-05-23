@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header" style="display: flex; align-items: center;">
            <a href="javascript:history.back()" style="margin-right: 10px; font-size: 20px; color: #333;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="pl-3">Tambah Kategori</h1>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('category.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="category">Nama Kategori</label>
                            <input type="text" name="category" id="category" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
