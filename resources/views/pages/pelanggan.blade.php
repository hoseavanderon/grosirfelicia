@extends('layouts.app')

@push('styles')
    <style>
        .customer-click {
            cursor: pointer;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }

        .customer-click:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .customer-click:active {
            transform: scale(0.98);
            transition: transform 0.1s;
            background-color: #f0f0f0;
        }
    </style>
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="pl-3">Langganan</h1>
        </div>

        <div class="section-body">
            <ul class="list-group">
                @forelse ($customers as $customer)
                    <li class="list-group-item d-flex justify-content-between align-items-center customer-click"
                        data-href="{{ route('pelanggan.show', $customer->id) }}">
                        <span class="text-dark">
                            {{ $customer->nama_pelanggan }}
                            <i class="fas fa-chevron-right ms-2 text-muted"></i>
                        </span>
                        <span class="badge bg-primary rounded-pill text-white">
                            {{ $customer->transactions_count }} Trx
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Belum ada pelanggan.</li>
                @endforelse
            </ul>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.customer-click').forEach(function(item) {
                    item.addEventListener('click', function() {
                        const targetUrl = this.dataset.href;
                        if (targetUrl) {
                            window.location.href = targetUrl;
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
