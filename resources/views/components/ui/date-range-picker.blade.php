<div class="surface-card relative rounded-3xl p-4 shadow-sm sm:p-5" @click.outside="calendarOpen = false">
    <button type="button" @click="calendarOpen = !calendarOpen"
        class="surface-card inline-flex w-full items-center justify-between gap-3 rounded-3xl px-5 py-3 text-left text-sm shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-md sm:w-auto">
        <span class="theme-text text-sm font-medium" x-text="formattedRange()"></span>
        <svg xmlns="http://www.w3.org/2000/svg" class="theme-text h-5 w-5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.25 7.5V6a2.25 2.25 0 114.5 0v1.5m0 0V6a2.25 2.25 0 114.5 0v1.5M3.75 9h16.5M5.25 19.5h13.5A2.25 2.25 0 0021 17.25V8.25A2.25 2.25 0 0018.75 6H5.25A2.25 2.25 0 003 8.25v8.999A2.25 2.25 0 005.25 19.5z" />
        </svg>
    </button>

    <div x-show="calendarOpen" x-transition x-cloak
        class="surface-card absolute left-0 z-20 mt-4 w-full max-w-md rounded-3xl p-4 shadow-2xl">
        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="previousMonth()"
                class="surface-card rounded-2xl px-3 py-2 text-xs font-medium">Sebelumnya</button>
            <div class="theme-text text-sm font-semibold" x-text="monthName()"></div>
            <button type="button" @click="nextMonth()"
                class="surface-card rounded-2xl px-3 py-2 text-xs font-medium">Berikutnya</button>
        </div>

        <div
            class="theme-text-muted mt-4 grid grid-cols-7 gap-2 text-center text-[10px] font-semibold uppercase tracking-[0.2em]">
            <template x-for="day in weekdays" :key="day">
                <div x-text="day"></div>
            </template>
        </div>

        <div class="theme-text mt-3 grid grid-cols-7 gap-2 text-center text-sm">
            <template x-for="(day, index) in monthDays()" :key="index">
                <button type="button" :class="day ? dayClass(day) : 'calendar-day calendar-day-empty'"
                    x-text="day ? day.getDate() : ''" @click="selectDay(day)" :disabled="!day">
                </button>
            </template>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" @click="resetDate()"
                class="surface-card rounded-2xl px-4 py-2 text-xs font-semibold">Reset</button>
            <button type="button" @click="applyDate()"
                class="rounded-2xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">Terapkan</button>
        </div>
    </div>
</div>
