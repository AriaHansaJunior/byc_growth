/**
 * BYC GROWTH — Universal Themed Select Dropdown
 * Converts standard HTML selects into smooth, glitch-free, organic BYC Growth themed dropdowns.
 */
export function initThemedSelects() {
    const selects = document.querySelectorAll('select.form-input, select.input-field, .roles-filter-form select, .admin-filter-bar select');

    selects.forEach(select => {
        // Skip hidden, searchable, multiple, or already initialized selects
        if (select.dataset.themedSelectInitialized === 'true' ||
            select.dataset.native === 'true' ||
            select.getAttribute('tabindex') === '-1' ||
            select.multiple ||
            select.closest('.byc-custom-select-wrap')) {
            return;
        }

        // Save original inline dimensions BEFORE hiding native select
        const originalHeight = select.style.height || (select.classList.contains('input-sm') ? '34px' : '40px');
        const originalFontSize = select.style.fontSize || '13.5px';

        // Mark as initialized
        select.dataset.themedSelectInitialized = 'true';

        // Create wrap
        const wrap = document.createElement('div');
        wrap.className = 'byc-custom-select-wrap';
        if (select.style.width) wrap.style.width = select.style.width;

        // Hide native select visually while keeping it in the DOM and form-submittable
        select.style.position = 'absolute';
        select.style.width = '1px';
        select.style.height = '1px';
        select.style.opacity = '0';
        select.style.pointerEvents = 'none';
        select.style.margin = '-1px';
        select.style.clip = 'rect(0,0,0,0)';
        select.style.overflow = 'hidden';

        // Insert wrap before select and move select inside wrap
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        // Build Trigger Button with proper vertical proportions
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'byc-custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        // Apply original height and font size properly
        trigger.style.height = originalHeight;
        trigger.style.minHeight = originalHeight;
        if (originalFontSize) trigger.style.fontSize = originalFontSize;

        const label = document.createElement('span');
        label.className = 'byc-custom-select-label';

        const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        chevron.setAttribute('class', 'byc-custom-select-chevron');
        chevron.setAttribute('viewBox', '0 0 24 24');
        chevron.setAttribute('fill', 'none');
        chevron.setAttribute('stroke', 'currentColor');
        chevron.setAttribute('stroke-width', '2.4');
        chevron.setAttribute('stroke-linecap', 'round');
        chevron.setAttribute('stroke-linejoin', 'round');
        chevron.innerHTML = '<polyline points="6 9 12 15 18 9"></polyline>';

        trigger.appendChild(label);
        trigger.appendChild(chevron);
        wrap.appendChild(trigger);

        // Build Options Menu
        const menu = document.createElement('div');
        menu.className = 'byc-custom-select-menu';
        menu.setAttribute('role', 'listbox');
        wrap.appendChild(menu);

        function renderOptions() {
            menu.innerHTML = '';
            Array.from(select.options).forEach((opt) => {
                const optEl = document.createElement('div');
                optEl.className = 'byc-custom-select-option' + (opt.selected ? ' is-selected' : '');
                optEl.dataset.value = opt.value;
                optEl.setAttribute('role', 'option');
                optEl.setAttribute('aria-selected', String(opt.selected));

                const optText = document.createElement('span');
                optText.textContent = opt.text;
                optEl.appendChild(optText);

                const check = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                check.setAttribute('class', 'byc-custom-select-check');
                check.setAttribute('viewBox', '0 0 24 24');
                check.setAttribute('fill', 'none');
                check.setAttribute('stroke', 'currentColor');
                check.setAttribute('stroke-width', '2.6');
                check.setAttribute('stroke-linecap', 'round');
                check.setAttribute('stroke-linejoin', 'round');
                check.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline>';
                optEl.appendChild(check);

                optEl.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectOption(opt.value);
                });

                menu.appendChild(optEl);
            });

            const selectedOpt = select.options[select.selectedIndex];
            label.textContent = selectedOpt ? selectedOpt.text : (select.options[0] ? select.options[0].text : '');
        }

        function selectOption(val) {
            select.value = val;
            const selectedOpt = select.options[select.selectedIndex];
            label.textContent = selectedOpt ? selectedOpt.text : '';

            menu.querySelectorAll('.byc-custom-select-option').forEach(el => {
                const isMatch = el.dataset.value === String(val);
                el.classList.toggle('is-selected', isMatch);
                el.setAttribute('aria-selected', String(isMatch));
            });

            close();

            // Dispatch input and change events for forms / auto-filtering
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function open() {
            // Close other open themed dropdowns
            document.querySelectorAll('.byc-custom-select-wrap.is-open').forEach(w => {
                if (w !== wrap) {
                    w.classList.remove('is-open');
                    const btn = w.querySelector('.byc-custom-select-trigger');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Viewport collision check: does it fit below?
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom - 16;
            const spaceAbove = rect.top - 16;
            if (spaceBelow < 200 && spaceAbove > spaceBelow) {
                wrap.classList.add('opens-up');
            } else {
                wrap.classList.remove('opens-up');
            }

            wrap.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        function close() {
            wrap.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function toggle() {
            if (wrap.classList.contains('is-open')) {
                close();
            } else {
                open();
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            toggle();
        });

        // Sync if select value changed externally (e.g. form reset, JS)
        select.addEventListener('change', () => {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt) {
                label.textContent = selectedOpt.text;
                menu.querySelectorAll('.byc-custom-select-option').forEach(el => {
                    const isMatch = el.dataset.value === String(select.value);
                    el.classList.toggle('is-selected', isMatch);
                    el.setAttribute('aria-selected', String(isMatch));
                });
            }
        });

        renderOptions();
    });

    // Close on outside click
    if (!window._bycThemedSelectDocClickBound) {
        window._bycThemedSelectDocClickBound = true;
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.byc-custom-select-wrap')) {
                document.querySelectorAll('.byc-custom-select-wrap.is-open').forEach(w => {
                    w.classList.remove('is-open');
                    const btn = w.querySelector('.byc-custom-select-trigger');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.byc-custom-select-wrap.is-open').forEach(w => {
                    w.classList.remove('is-open');
                    const btn = w.querySelector('.byc-custom-select-trigger');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                        btn.focus();
                    }
                });
            }
        });
    }
}

