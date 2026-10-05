/**
 * BYC GROWTH — Bespoke Enhanced Datepicker
 * Implements Allianz Multi-Level Navigation:
 * - Day View (with "Choose month and year" shortcut)
 * - Year View (4x5 20-Year Decade Grid: 2000-2019, 2020-2039)
 * - Month View (4x3 12-Month Grid)
 * - Symmetrical vertically centered chevrons
 * - Single "Today" action button in footer
 * - Max / Min date constraints (e.g. max today for birthdays and transfers)
 * - Teleported to document.body with fixed smart-collision positioning (drop-up/drop-down)
 * - Click outside & Escape to close
 */

const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const MONTH_SHORT = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];

const WEEKDAYS = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

// Format a Date object as YYYY-MM-DD
function formatDateISO(d) {
    if (!d || isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Format a Date object as friendly display: "05 Oct 2026"
function formatDateDisplay(d) {
    if (!d || isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const month = MONTH_SHORT[d.getMonth()];
    const year = d.getFullYear();
    return `${day} ${month} ${year}`;
}

// Parse YYYY-MM-DD or standard date string safely to local Date
function parseDateString(str) {
    if (!str) return null;
    const parts = str.split('-');
    if (parts.length === 3) {
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);
        if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
            return new Date(y, m, d);
        }
    }
    const fallback = new Date(str);
    return isNaN(fallback.getTime()) ? null : fallback;
}

function startOfDay(d) {
    if (!d || isNaN(d.getTime())) return null;
    return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0, 0);
}

function endOfDay(d) {
    if (!d || isNaN(d.getTime())) return null;
    return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59, 999);
}

class BycDatePicker {
    constructor(originalInput) {
        this.originalInput = originalInput;
        this.originalInput._bycDatePicker = this;

        // Setup min and max constraints
        this.initConstraints();

        // Current selection
        const initialVal = this.originalInput.value || this.originalInput.getAttribute('value');
        const parsedInitial = parseDateString(initialVal);
        if (parsedInitial && this.maxDate && parsedInitial > this.maxDate) {
            this.selectedDate = null;
        } else if (parsedInitial && this.minDate && parsedInitial < this.minDate) {
            this.selectedDate = null;
        } else {
            this.selectedDate = parsedInitial;
        }

        this.viewDate = this.selectedDate ? new Date(this.selectedDate) : new Date();
        if (!this.selectedDate && this.maxDate && this.viewDate > this.maxDate) {
            this.viewDate = new Date(this.maxDate);
        }

        // View mode: 'days' | 'years' | 'months'
        this.currentView = 'days';

        // Year grid start year (rounded to nearest 20-year span, e.g. 2020)
        this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;

        // Setup DOM
        this.setupDOM();
        this.bindEvents();
    }

    initConstraints() {
        const rawMax = this.originalInput.getAttribute('max') || this.originalInput.dataset.max;
        const inputName = (this.originalInput.name || '').toLowerCase();
        const inputId = (this.originalInput.id || '').toLowerCase();
        const isBirthdayOrTransfer = inputName.includes('birth') || inputId.includes('birth') || 
                                     inputName.includes('dob') || inputId.includes('dob') ||
                                     (inputName === 'date' && !inputName.includes('event') && !inputName.includes('start'));

        if (rawMax === 'today' || (!rawMax && isBirthdayOrTransfer)) {
            this.maxDate = endOfDay(new Date());
        } else if (rawMax) {
            const parsed = parseDateString(rawMax);
            this.maxDate = parsed ? endOfDay(parsed) : null;
        } else {
            this.maxDate = null;
        }

        const rawMin = this.originalInput.getAttribute('min') || this.originalInput.dataset.min;
        if (rawMin === 'today') {
            this.minDate = startOfDay(new Date());
        } else if (rawMin) {
            const parsedMin = parseDateString(rawMin);
            this.minDate = parsedMin ? startOfDay(parsedMin) : null;
        } else {
            this.minDate = null;
        }
    }

    setupDOM() {
        // Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'byc-date-wrapper';

        // Create display input
        this.displayInput = document.createElement('input');
        this.displayInput.type = 'text';
        this.displayInput.readOnly = true;
        this.displayInput.className = (this.originalInput.className || 'form-input') + ' byc-date-display';
        this.displayInput.placeholder = this.originalInput.placeholder || 'Select date...';
        this.displayInput.disabled = this.originalInput.disabled;

        // Sync initial display value
        if (this.selectedDate) {
            this.displayInput.value = formatDateDisplay(this.selectedDate);
        }

        // Hide original input and insert wrapper in DOM
        const parent = this.originalInput.parentNode;
        parent.insertBefore(this.wrapper, this.originalInput);
        this.wrapper.appendChild(this.originalInput);
        this.wrapper.appendChild(this.displayInput);

        this.originalInput.style.display = 'none';

        // Create popup card and teleport to document.body so modal overflow:hidden never clips it
        this.popup = document.createElement('div');
        this.popup.className = 'byc-dp-popup';
        this.popup.style.display = 'none';
        document.body.appendChild(this.popup);
    }

    bindEvents() {
        // Toggle on display input click
        this.displayInput.addEventListener('click', (e) => {
            e.stopPropagation();
            if (this.displayInput.disabled) return;
            if (this.isOpen()) {
                this.close();
            } else {
                this.open();
            }
        });

        // Prevent popup clicks from closing
        this.popup.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Watch for disabled state and constraint changes on original input
        const observer = new MutationObserver(() => {
            this.displayInput.disabled = this.originalInput.disabled;
            this.initConstraints();
            if (this.isOpen()) {
                this.render();
            }
        });
        observer.observe(this.originalInput, { attributes: true, attributeFilter: ['disabled', 'max', 'min'] });

        // Intercept programmatic value setter on originalInput
        const originalSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
        if (originalSetter) {
            const self = this;
            Object.defineProperty(this.originalInput, 'value', {
                set: function(val) {
                    originalSetter.call(this, val);
                    const parsed = parseDateString(val);
                    if (parsed && ((self.maxDate && parsed > self.maxDate) || (self.minDate && parsed < self.minDate))) {
                        self.selectedDate = null;
                        self.displayInput.value = '';
                    } else {
                        self.selectedDate = parsed;
                        if (parsed) {
                            self.viewDate = new Date(parsed);
                            self.yearRangeStart = Math.floor(parsed.getFullYear() / 20) * 20;
                            self.displayInput.value = formatDateDisplay(parsed);
                        } else {
                            self.displayInput.value = '';
                        }
                    }
                    if (self.isOpen()) {
                        self.render();
                    }
                },
                get: function() {
                    return this.getAttribute('value') || '';
                },
                configurable: true
            });
        }
    }

    isOpen() {
        return this.popup.style.display !== 'none';
    }

    updatePosition() {
        if (!this.isOpen()) return;
        const rect = this.displayInput.getBoundingClientRect();
        if (rect.width === 0 && rect.height === 0) {
            this.close();
            return;
        }

        const popupWidth = 320;
        const popupHeight = this.popup.offsetHeight || 360;
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;

        const spaceBelow = viewportHeight - rect.bottom;
        const spaceAbove = rect.top;

        let top;
        // Prefer drop-down, but drop up if spaceBelow is not enough and spaceAbove is larger
        if (spaceBelow < popupHeight + 12 && spaceAbove > spaceBelow) {
            top = Math.max(10, rect.top - popupHeight - 8);
            this.popup.classList.add('is-dropup');
        } else {
            top = rect.bottom + 8;
            this.popup.classList.remove('is-dropup');
        }

        let left = rect.left;
        if (left + popupWidth > viewportWidth - 12) {
            left = Math.max(12, viewportWidth - popupWidth - 12);
        }
        if (left < 12) left = 12;

        this.popup.style.position = 'fixed';
        this.popup.style.top = `${Math.round(top)}px`;
        this.popup.style.left = `${Math.round(left)}px`;
        this.popup.style.zIndex = '999999';
    }

    open() {
        // Close any other open datepickers
        document.querySelectorAll('.byc-dp-popup').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.byc-date-display').forEach(d => d.classList.remove('is-active'));

        this.initConstraints();

        // Reset view to days whenever opening
        this.currentView = 'days';
        if (this.selectedDate) {
            this.viewDate = new Date(this.selectedDate);
        } else {
            const now = new Date();
            if (this.maxDate && now > this.maxDate) {
                this.viewDate = new Date(this.maxDate);
            } else {
                this.viewDate = new Date();
            }
        }
        this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;

        this.render();
        this.popup.style.display = 'block';
        this.displayInput.classList.add('is-active');

        this.updatePosition();

        // Attach listeners for dynamic repositioning on scroll/resize
        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, true);
            window.removeEventListener('resize', this._scrollHandler);
        }
        this._scrollHandler = (e) => {
            if (!this.isOpen()) return;
            if (e && e.target && (e.target === this.popup || this.popup.contains(e.target))) {
                return;
            }
            const rect = this.displayInput.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                this.close();
            } else {
                this.updatePosition();
            }
        };
        window.addEventListener('scroll', this._scrollHandler, true);
        window.addEventListener('resize', this._scrollHandler);
    }

    close() {
        this.popup.style.display = 'none';
        this.displayInput.classList.remove('is-active');
        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, true);
            window.removeEventListener('resize', this._scrollHandler);
            this._scrollHandler = null;
        }
    }

    render() {
        this.popup.innerHTML = '';

        if (this.currentView === 'days') {
            this.renderDaysView();
        } else if (this.currentView === 'years') {
            this.renderYearsView();
        } else if (this.currentView === 'months') {
            this.renderMonthsView();
        }

        // Always render footer with ONLY Today button
        this.renderFooter();

        // Re-adjust position if height changed
        requestAnimationFrame(() => this.updatePosition());
    }

    // -------------------------------------------------------------------------
    // VIEW 1: DAYS VIEW
    // -------------------------------------------------------------------------
    renderDaysView() {
        const header = document.createElement('div');
        header.className = 'byc-dp-header';

        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'byc-dp-arrow byc-dp-prev';
        prevBtn.setAttribute('aria-label', 'Previous Month');
        prevBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>`;
        
        // Disable prev button if whole prev month is before minDate
        if (this.minDate) {
            const prevMonthLastDate = new Date(year, month, 0, 23, 59, 59, 999);
            if (prevMonthLastDate < this.minDate) {
                prevBtn.disabled = true;
                prevBtn.classList.add('is-disabled');
            }
        }
        prevBtn.addEventListener('click', () => {
            if (prevBtn.disabled) return;
            this.viewDate.setMonth(this.viewDate.getMonth() - 1);
            this.render();
        });

        const center = document.createElement('div');
        center.className = 'byc-dp-header-center';

        const titleBtn = document.createElement('div');
        titleBtn.className = 'byc-dp-title';
        titleBtn.textContent = `${MONTH_NAMES[this.viewDate.getMonth()]} ${this.viewDate.getFullYear()}`;
        titleBtn.addEventListener('click', () => {
            this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;
            this.currentView = 'years';
            this.render();
        });

        const subLink = document.createElement('button');
        subLink.type = 'button';
        subLink.className = 'byc-dp-sublink';
        subLink.textContent = 'Choose month and year';
        subLink.addEventListener('click', () => {
            this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;
            this.currentView = 'years';
            this.render();
        });

        center.appendChild(titleBtn);
        center.appendChild(subLink);

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'byc-dp-arrow byc-dp-next';
        nextBtn.setAttribute('aria-label', 'Next Month');
        nextBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>`;

        // Disable next button if whole next month is beyond maxDate
        if (this.maxDate) {
            const nextMonthFirstDate = new Date(year, month + 1, 1);
            if (nextMonthFirstDate > this.maxDate) {
                nextBtn.disabled = true;
                nextBtn.classList.add('is-disabled');
                nextBtn.setAttribute('title', 'Next month is beyond maximum allowed date');
            }
        }
        nextBtn.addEventListener('click', () => {
            if (nextBtn.disabled) return;
            this.viewDate.setMonth(this.viewDate.getMonth() + 1);
            this.render();
        });

        header.appendChild(prevBtn);
        header.appendChild(center);
        header.appendChild(nextBtn);
        this.popup.appendChild(header);

        // Weekdays Row
        const weekdaysRow = document.createElement('div');
        weekdaysRow.className = 'byc-dp-weekdays';
        WEEKDAYS.forEach(day => {
            const span = document.createElement('span');
            span.className = 'byc-dp-weekday';
            span.textContent = day;
            weekdaysRow.appendChild(span);
        });
        this.popup.appendChild(weekdaysRow);

        // Days Grid (7 Columns)
        const daysGrid = document.createElement('div');
        daysGrid.className = 'byc-dp-days-grid';

        // First day of current month (0 = Sun, 1 = Mon...)
        const firstDayIndex = new Date(year, month, 1).getDay();
        // Number of days in current month
        const totalDays = new Date(year, month + 1, 0).getDate();
        // Number of days in previous month
        const prevMonthTotalDays = new Date(year, month, 0).getDate();

        const today = new Date();
        const todayStr = formatDateISO(today);
        const selectedStr = this.selectedDate ? formatDateISO(this.selectedDate) : '';

        // 1. Previous month trailing days
        for (let i = firstDayIndex - 1; i >= 0; i--) {
            const dayNum = prevMonthTotalDays - i;
            const prevDate = new Date(year, month - 1, dayNum);
            const btn = this.createDayButton(dayNum, prevDate, true, todayStr, selectedStr);
            daysGrid.appendChild(btn);
        }

        // 2. Current month days
        for (let d = 1; d <= totalDays; d++) {
            const currentDate = new Date(year, month, d);
            const btn = this.createDayButton(d, currentDate, false, todayStr, selectedStr);
            daysGrid.appendChild(btn);
        }

        // 3. Next month leading days (fill total cells to 35 or 42)
        const currentCellsCount = firstDayIndex + totalDays;
        const totalSlots = currentCellsCount > 35 ? 42 : 35;
        const nextDaysCount = totalSlots - currentCellsCount;

        for (let n = 1; n <= nextDaysCount; n++) {
            const nextDate = new Date(year, month + 1, n);
            const btn = this.createDayButton(n, nextDate, true, todayStr, selectedStr);
            daysGrid.appendChild(btn);
        }

        this.popup.appendChild(daysGrid);
    }

    createDayButton(dayNum, dateObj, isOtherMonth, todayStr, selectedStr) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'byc-dp-day-cell';
        btn.textContent = dayNum;

        const dateStr = formatDateISO(dateObj);
        btn.dataset.date = dateStr;

        if (isOtherMonth) {
            btn.classList.add('is-other-month');
        }
        if (dateStr === todayStr) {
            btn.classList.add('is-today');
        }
        if (dateStr === selectedStr) {
            btn.classList.add('is-selected');
        }

        const isPastMax = this.maxDate && dateObj > this.maxDate;
        const isBeforeMin = this.minDate && dateObj < this.minDate;

        if (isPastMax || isBeforeMin) {
            btn.disabled = true;
            btn.classList.add('is-disabled');
            btn.setAttribute('aria-disabled', 'true');
            btn.setAttribute('title', isPastMax ? 'Cannot select date in the future' : 'Date is before minimum allowed');
        } else {
            btn.addEventListener('click', () => {
                this.selectDate(dateObj);
                this.close();
            });
        }

        return btn;
    }

    // -------------------------------------------------------------------------
    // VIEW 2: YEAR / DECADE VIEW (Allianz 4x5 20-Year Grid)
    // -------------------------------------------------------------------------
    renderYearsView() {
        const header = document.createElement('div');
        header.className = 'byc-dp-header';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'byc-dp-arrow byc-dp-prev';
        prevBtn.setAttribute('aria-label', 'Previous 20 Years');
        prevBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>`;
        
        if (this.minDate) {
            const prevDecadeEnd = new Date(this.yearRangeStart - 1, 11, 31, 23, 59, 59, 999);
            if (prevDecadeEnd < this.minDate) {
                prevBtn.disabled = true;
                prevBtn.classList.add('is-disabled');
            }
        }
        prevBtn.addEventListener('click', () => {
            if (prevBtn.disabled) return;
            this.yearRangeStart -= 20;
            this.render();
        });

        const center = document.createElement('div');
        center.className = 'byc-dp-header-center';

        const title = document.createElement('div');
        title.className = 'byc-dp-title';
        title.textContent = `${this.yearRangeStart} – ${this.yearRangeStart + 19}`;

        const subLink = document.createElement('button');
        subLink.type = 'button';
        subLink.className = 'byc-dp-sublink';
        subLink.textContent = 'Choose date';
        subLink.addEventListener('click', () => {
            this.currentView = 'days';
            this.render();
        });

        center.appendChild(title);
        center.appendChild(subLink);

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'byc-dp-arrow byc-dp-next';
        nextBtn.setAttribute('aria-label', 'Next 20 Years');
        nextBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>`;

        if (this.maxDate) {
            const nextDecadeStart = new Date(this.yearRangeStart + 20, 0, 1);
            if (nextDecadeStart > this.maxDate) {
                nextBtn.disabled = true;
                nextBtn.classList.add('is-disabled');
                nextBtn.setAttribute('title', 'Future years are disabled');
            }
        }
        nextBtn.addEventListener('click', () => {
            if (nextBtn.disabled) return;
            this.yearRangeStart += 20;
            this.render();
        });

        header.appendChild(prevBtn);
        header.appendChild(center);
        header.appendChild(nextBtn);
        this.popup.appendChild(header);

        // 4 Columns x 5 Rows Grid (20 Years)
        const yearsGrid = document.createElement('div');
        yearsGrid.className = 'byc-dp-years-grid';

        const currentYear = new Date().getFullYear();
        const selectedYear = this.selectedDate ? this.selectedDate.getFullYear() : null;

        for (let y = this.yearRangeStart; y < this.yearRangeStart + 20; y++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'byc-dp-year-cell';
            btn.textContent = y;

            if (y === currentYear) {
                btn.classList.add('is-current-year');
            }
            if (y === selectedYear) {
                btn.classList.add('is-selected');
            }

            const yearStart = new Date(y, 0, 1);
            const yearEnd = new Date(y, 11, 31, 23, 59, 59, 999);
            const isPastMax = this.maxDate && yearStart > this.maxDate;
            const isBeforeMin = this.minDate && yearEnd < this.minDate;

            if (isPastMax || isBeforeMin) {
                btn.disabled = true;
                btn.classList.add('is-disabled');
                btn.setAttribute('aria-disabled', 'true');
                btn.setAttribute('title', isPastMax ? 'Cannot select future year' : 'Year before minimum allowed');
            } else {
                btn.addEventListener('click', () => {
                    this.viewDate.setFullYear(y);
                    this.currentView = 'months';
                    this.render();
                });
            }

            yearsGrid.appendChild(btn);
        }

        this.popup.appendChild(yearsGrid);
    }

    // -------------------------------------------------------------------------
    // VIEW 3: MONTH VIEW (4x3 12-Month Grid)
    // -------------------------------------------------------------------------
    renderMonthsView() {
        const header = document.createElement('div');
        header.className = 'byc-dp-header';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'byc-dp-arrow byc-dp-prev';
        prevBtn.setAttribute('aria-label', 'Previous Year');
        prevBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>`;

        if (this.minDate) {
            const prevYearEnd = new Date(this.viewDate.getFullYear() - 1, 11, 31, 23, 59, 59, 999);
            if (prevYearEnd < this.minDate) {
                prevBtn.disabled = true;
                prevBtn.classList.add('is-disabled');
            }
        }
        prevBtn.addEventListener('click', () => {
            if (prevBtn.disabled) return;
            this.viewDate.setFullYear(this.viewDate.getFullYear() - 1);
            this.render();
        });

        const center = document.createElement('div');
        center.className = 'byc-dp-header-center';

        const title = document.createElement('div');
        title.className = 'byc-dp-title';
        title.textContent = this.viewDate.getFullYear();
        title.addEventListener('click', () => {
            this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;
            this.currentView = 'years';
            this.render();
        });

        const subLink = document.createElement('button');
        subLink.type = 'button';
        subLink.className = 'byc-dp-sublink';
        subLink.textContent = 'Choose year';
        subLink.addEventListener('click', () => {
            this.yearRangeStart = Math.floor(this.viewDate.getFullYear() / 20) * 20;
            this.currentView = 'years';
            this.render();
        });

        center.appendChild(title);
        center.appendChild(subLink);

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'byc-dp-arrow byc-dp-next';
        nextBtn.setAttribute('aria-label', 'Next Year');
        nextBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>`;

        if (this.maxDate) {
            const nextYearStart = new Date(this.viewDate.getFullYear() + 1, 0, 1);
            if (nextYearStart > this.maxDate) {
                nextBtn.disabled = true;
                nextBtn.classList.add('is-disabled');
                nextBtn.setAttribute('title', 'Future years are disabled');
            }
        }
        nextBtn.addEventListener('click', () => {
            if (nextBtn.disabled) return;
            this.viewDate.setFullYear(this.viewDate.getFullYear() + 1);
            this.render();
        });

        header.appendChild(prevBtn);
        header.appendChild(center);
        header.appendChild(nextBtn);
        this.popup.appendChild(header);

        // 4 Columns x 3 Rows Grid (12 Months)
        const monthsGrid = document.createElement('div');
        monthsGrid.className = 'byc-dp-months-grid';

        const currentMonth = new Date().getMonth();
        const currentYear = new Date().getFullYear();
        const isThisYear = this.viewDate.getFullYear() === currentYear;

        MONTH_SHORT.forEach((name, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'byc-dp-month-cell';
            btn.textContent = name;

            if (isThisYear && idx === currentMonth) {
                btn.classList.add('is-current-month');
            }
            if (this.selectedDate && this.selectedDate.getFullYear() === this.viewDate.getFullYear() && this.selectedDate.getMonth() === idx) {
                btn.classList.add('is-selected');
            }

            const monthStart = new Date(this.viewDate.getFullYear(), idx, 1);
            const monthEnd = new Date(this.viewDate.getFullYear(), idx + 1, 0, 23, 59, 59, 999);
            const isPastMax = this.maxDate && monthStart > this.maxDate;
            const isBeforeMin = this.minDate && monthEnd < this.minDate;

            if (isPastMax || isBeforeMin) {
                btn.disabled = true;
                btn.classList.add('is-disabled');
                btn.setAttribute('aria-disabled', 'true');
                btn.setAttribute('title', isPastMax ? 'Cannot select future month' : 'Month before minimum allowed');
            } else {
                btn.addEventListener('click', () => {
                    this.viewDate.setMonth(idx);
                    this.currentView = 'days';
                    this.render();
                });
            }

            monthsGrid.appendChild(btn);
        });

        this.popup.appendChild(monthsGrid);
    }

    // -------------------------------------------------------------------------
    // FOOTER: ONLY 'Today' Button
    // -------------------------------------------------------------------------
    renderFooter() {
        const footer = document.createElement('div');
        footer.className = 'byc-dp-footer';

        const todayBtn = document.createElement('button');
        todayBtn.type = 'button';
        todayBtn.className = 'byc-dp-today-btn';
        todayBtn.textContent = 'Today';

        const today = new Date();
        const isPastMax = this.maxDate && today > this.maxDate;
        const isBeforeMin = this.minDate && today < this.minDate;

        if (isPastMax || isBeforeMin) {
            todayBtn.disabled = true;
            todayBtn.classList.add('is-disabled');
            todayBtn.setAttribute('title', 'Today is outside allowed range');
        } else {
            todayBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectDate(new Date());
                this.close();
            });
        }

        footer.appendChild(todayBtn);
        this.popup.appendChild(footer);
    }

    // Select a date and synchronize inputs
    selectDate(dateObj) {
        if (!dateObj) return;
        const target = new Date(dateObj);

        if (this.maxDate && target > this.maxDate) {
            return;
        }
        if (this.minDate && target < this.minDate) {
            return;
        }

        this.selectedDate = new Date(target);
        this.viewDate = new Date(target);

        const isoStr = formatDateISO(this.selectedDate);
        const displayStr = formatDateDisplay(this.selectedDate);

        // Set display input
        this.displayInput.value = displayStr;

        // Set original input value (via prototype setter so it doesn't loop)
        const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
        if (setter) {
            setter.call(this.originalInput, isoStr);
        } else {
            this.originalInput.value = isoStr;
        }

        // Trigger native change and input events on original input
        this.originalInput.dispatchEvent(new Event('input', { bubbles: true }));
        this.originalInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

// Global click-outside listener to close any open datepickers
document.addEventListener('click', (e) => {
    if (!e.target.closest('.byc-date-wrapper') && !e.target.closest('.byc-dp-popup')) {
        document.querySelectorAll('.byc-dp-popup').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.byc-date-display').forEach(d => d.classList.remove('is-active'));
    }
});

// Global Escape listener to close any open datepickers
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.byc-dp-popup').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.byc-date-display').forEach(d => d.classList.remove('is-active'));
    }
});

/**
 * Universal Datepicker Initializer
 * Auto-enhances all input[type="date"], input.datepicker, and input[data-datepicker]
 */
export function initDatepickers(root = document) {
    if (!root) return;
    const inputs = root.querySelectorAll('input[type="date"], input.datepicker, input[data-datepicker]');
    inputs.forEach(input => {
        if (!input._bycDatePicker) {
            new BycDatePicker(input);
        }
    });
}

/**
 * Global Helper for Programmatically Setting Datepicker Value
 */
window.setDatePickerValue = function(elementOrId, dateVal) {
    const el = typeof elementOrId === 'string' ? document.getElementById(elementOrId) : elementOrId;
    if (!el) return;

    if (el._bycDatePicker) {
        if (dateVal) {
            const parsed = parseDateString(dateVal);
            if (parsed) {
                if (el._bycDatePicker.maxDate && parsed > el._bycDatePicker.maxDate) {
                    console.warn('[BycDatePicker] Date exceeds maximum allowed:', dateVal);
                    return;
                }
                el._bycDatePicker.selectDate(parsed);
            } else {
                el.value = '';
                el._bycDatePicker.selectedDate = null;
                el._bycDatePicker.displayInput.value = '';
            }
        } else {
            el.value = '';
            el._bycDatePicker.selectedDate = null;
            el._bycDatePicker.displayInput.value = '';
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } else {
        el.value = dateVal || '';
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

window.initDatepickers = initDatepickers;
