@extends('layouts.app')

@section('title', 'Langganan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/langganan.css') }}">
@endpush

@section('content')

    <div class="langganan-page space-y-6">
        <div class="langganan-header">
            <h1 class="langganan-title theme-text">Langganan Pelanggan</h1>
            <p class="langganan-subtitle theme-text-muted">
                Daftar pelanggan dan riwayat loyalitas toko Anda.
            </p>
        </div>

        @if ($customers->isEmpty())
            <div class="langganan-empty surface-card rounded-3xl shadow-sm">
                Belum ada pelanggan terdaftar.
            </div>
        @else
            <div class="langganan-grid">
                @foreach ($customers as $customer)
                    <a href="{{ route('customers.show', $customer->id) }}" class="langganan-customer-card">
                        <div class="min-w-0">
                            <p class="langganan-customer-name theme-text truncate">
                                {{ $customer->nama_pelanggan }}
                            </p>
                            <p class="langganan-customer-phone theme-text-muted">
                                {{ $customer->no_telp ?: '-' }}
                            </p>
                        </div>

                        <svg xmlns="http://www.w3.org/2000/svg" class="langganan-card-arrow h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

@endsection
