function createDateRangePicker(options = {}) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const defaultFrom =
        options.defaultFrom ?? new Date(today.getFullYear(), 0, 1);
    const defaultTo = options.defaultTo ?? new Date(today);
    const onApply = options.onApply ?? (() => {});

    defaultFrom.setHours(0, 0, 0, 0);
    defaultTo.setHours(0, 0, 0, 0);

    return {
        calendarOpen: false,
        fromDate: new Date(defaultFrom),
        toDate: new Date(defaultTo),
        displayMonth: new Date(defaultFrom),
        weekdays: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],

        formattedRange() {
            if (!this.fromDate) {
                return "Pilih tanggal";
            }

            const fromLabel = this.formatShortDate(this.fromDate);

            if (this.toDate && !this.isSameDate(this.fromDate, this.toDate)) {
                const toLabel = this.formatShortDate(this.toDate);
                return `${fromLabel} - ${toLabel}`;
            }

            return fromLabel;
        },

        monthName() {
            return this.displayMonth.toLocaleDateString("id-ID", {
                month: "long",
                year: "numeric",
            });
        },

        monthDays() {
            const year = this.displayMonth.getFullYear();
            const month = this.displayMonth.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            const days = [];

            for (let i = 0; i < firstDay; i++) {
                days.push(null);
            }

            for (let day = 1; day <= totalDays; day++) {
                days.push(new Date(year, month, day));
            }

            return days;
        },

        formatShortDate(date) {
            return date.toLocaleDateString("id-ID", {
                day: "numeric",
                month: "long",
            });
        },

        formatApiDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },

        isSameDate(a, b) {
            if (!a || !b) {
                return false;
            }

            return a.toDateString() === b.toDateString();
        },

        isInRange(day) {
            if (!day || !this.fromDate || !this.toDate) {
                return false;
            }

            const start = Math.min(
                this.fromDate.getTime(),
                this.toDate.getTime(),
            );
            const end = Math.max(
                this.fromDate.getTime(),
                this.toDate.getTime(),
            );

            return day.getTime() > start && day.getTime() < end;
        },

        dayClass(day) {
            if (!day) {
                return "calendar-day calendar-day-empty";
            }

            if (
                this.isSameDate(day, this.fromDate) ||
                this.isSameDate(day, this.toDate)
            ) {
                return "calendar-day calendar-day-selected";
            }

            if (this.isInRange(day)) {
                return "calendar-day calendar-day-in-range";
            }

            return "calendar-day calendar-day-default";
        },

        selectDay(day) {
            if (!day) {
                return;
            }

            if (!this.fromDate || (this.fromDate && this.toDate)) {
                this.fromDate = new Date(day);
                this.toDate = null;
                this.displayMonth = new Date(
                    day.getFullYear(),
                    day.getMonth(),
                    1,
                );
                return;
            }

            if (this.isSameDate(day, this.fromDate)) {
                this.toDate = new Date(day);
                this.applyDate();
                return;
            }

            if (day.getTime() < this.fromDate.getTime()) {
                this.toDate = this.fromDate;
                this.fromDate = new Date(day);
            } else {
                this.toDate = new Date(day);
            }

            this.applyDate();
        },

        previousMonth() {
            this.displayMonth = new Date(
                this.displayMonth.getFullYear(),
                this.displayMonth.getMonth() - 1,
                1,
            );
        },

        nextMonth() {
            this.displayMonth = new Date(
                this.displayMonth.getFullYear(),
                this.displayMonth.getMonth() + 1,
                1,
            );
        },

        resetDate() {
            this.fromDate = new Date(defaultFrom);
            this.toDate = new Date(defaultTo);
            this.displayMonth = new Date(defaultFrom);
            this.calendarOpen = false;
            onApply.call(this);
        },

        applyDate() {
            if (!this.toDate) {
                this.toDate = new Date(this.fromDate);
            }

            this.calendarOpen = false;
            onApply.call(this);
        },
    };
}
