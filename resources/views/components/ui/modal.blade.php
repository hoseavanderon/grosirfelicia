@props(['show', 'maxWidth' => 'md'])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-lg',
        '2xl' => 'max-w-2xl',
    ];

    $sizeClass = $sizes[$maxWidth] ?? 'max-w-md';
@endphp

<template x-teleport="body">
    <div x-show="{{ $show }}" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"         class="fixed inset-0 z-[100] flex items-center justify-center p-4">

        <div @click="{{ $show }} = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>

        <div x-transition:enter="transition ease-out duration-220"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-180"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="
                relative
                z-[1]
                w-full
                {{ $sizeClass }}

                bg-[var(--card)]
                text-[var(--text)]

                rounded-[28px]

                shadow-[0_20px_60px_rgba(0,0,0,.18)]

                overflow-hidden
            ">

            {{ $slot }}

        </div>

    </div>
</template>
