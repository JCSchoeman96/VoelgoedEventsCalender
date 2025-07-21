var VGCalendar = (function () {
    'use strict';

    /******************************************************************************
    Copyright (c) Microsoft Corporation.

    Permission to use, copy, modify, and/or distribute this software for any
    purpose with or without fee is hereby granted.

    THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
    REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
    INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
    LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
    OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
    PERFORMANCE OF THIS SOFTWARE.
    ***************************************************************************** */
    /* global Reflect, Promise, SuppressedError, Symbol, Iterator */


    function __awaiter(thisArg, _arguments, P, generator) {
        function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
        return new (P || (P = Promise))(function (resolve, reject) {
            function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
            function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
            function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
            step((generator = generator.apply(thisArg, _arguments || [])).next());
        });
    }

    typeof SuppressedError === "function" ? SuppressedError : function (error, suppressed, message) {
        var e = new Error(message);
        return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
    };

    function debounce(fn, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), wait);
        };
    }
    function supportsSession() {
        try {
            const key = '__test';
            sessionStorage.setItem(key, '1');
            sessionStorage.removeItem(key);
            return true;
        }
        catch (_a) {
            return false;
        }
    }
    function getCache(key) {
        if (supportsSession()) {
            const item = sessionStorage.getItem(key);
            if (item) {
                try {
                    const parsed = JSON.parse(item);
                    if (!parsed.exp || parsed.exp > Date.now()) {
                        return parsed.value;
                    }
                }
                catch (_a) {
                    return null;
                }
            }
        }
        return null;
    }
    function setCache(key, value, ttl = 300000) {
        if (supportsSession()) {
            const payload = { value, exp: Date.now() + ttl };
            try {
                sessionStorage.setItem(key, JSON.stringify(payload));
                const indexKey = 'vgEvents_cache_index';
                const raw = sessionStorage.getItem(indexKey);
                const list = raw ? JSON.parse(raw) : [];
                const pos = list.indexOf(key);
                if (pos !== -1) list.splice(pos, 1);
                list.unshift(key);
                while (list.length > 3) {
                    const old = list.pop();
                    if (old)
                        sessionStorage.removeItem(old);
                }
                sessionStorage.setItem(indexKey, JSON.stringify(list));
            }
            catch (_a) {
                /* noop */
            }
        }
    }
    function supportsDateInput() {
        const input = document.createElement('input');
        input.setAttribute('type', 'date');
        const value = 'not-a-date';
        input.setAttribute('value', value);
        return input.value !== value;
    }

    function fetchEvents(url) {
        return __awaiter(this, void 0, void 0, function* () {
            const cached = getCache(url);
            if (cached) {
                return cached;
            }
            const res = yield fetch(url);
            const data = yield res.json();
            setCache(url, data, 300000);
            return data;
        });
    }

    function setPressed(el, pressed) {
        el.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    }
    function announce(id, msg) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = msg;
        }
    }

    function initFilters(filters, onChange) {
        if (supportsDateInput()) {
            filters.startDate.type = 'date';
            filters.endDate.type = 'date';
        }
        filters.typeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filters.typeButtons.forEach(b => setPressed(b, false));
                btn.classList.add('active');
                setPressed(btn, true);
                onChange();
            });
            btn.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const buttons = Array.from(filters.typeButtons);
                    const idx = buttons.indexOf(btn);
                    const next = buttons[idx + 1] || buttons[0];
                    next.focus();
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const buttons = Array.from(filters.typeButtons);
                    const idx = buttons.indexOf(btn);
                    const prev = buttons[idx - 1] || buttons[buttons.length - 1];
                    prev.focus();
                }
            });
        });
        const debounced = debounce(onChange, 300);
        [filters.startDate, filters.endDate, filters.searchBar, filters.monthFilter, filters.townFilter].forEach(el => {
            el.addEventListener('change', debounced);
            el.addEventListener('input', debounced);
        });
        filters.resetBtn.addEventListener('click', () => {
            filters.startDate.value = '';
            filters.endDate.value = '';
            filters.searchBar.value = '';
            filters.monthFilter.value = '';
            filters.townFilter.value = '';
            filters.typeButtons.forEach(b => {
                b.classList.remove('active');
                setPressed(b, false);
            });
            onChange();
        });
    }

    function updatePagination(current, total) {
        const info = document.getElementById('page-info');
        const prev = document.getElementById('prev-page');
        const next = document.getElementById('next-page');
        if (info)
            info.textContent = `${current} / ${total}`;
        if (prev)
            prev.disabled = current === 1;
        if (next)
            next.disabled = current === total;
        announce('vg-aria-announcer', `${current} of ${total}`);
    }

    let link = null;
    function prefetchNext(url) {
        if (!link) {
            link = document.createElement('link');
            link.rel = 'prefetch';
            document.head.appendChild(link);
        }
        link.href = url;
    }

    function observeLoad(cb) {
        const target = document.getElementById('elementor-loop-content');
        if (!target || !('IntersectionObserver' in window)) {
            cb();
            return;
        }
        const io = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                io.disconnect();
                cb();
            }
        }, { rootMargin: '200px' });
        io.observe(target);
    }

    class Calendar {
        constructor(cfg) {
            this.currentPage = 1;
            this.totalPages = 1;
            this.config = cfg;
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => this.init());
            }
            else {
                document.addEventListener('DOMContentLoaded', () => this.init());
            }
        }
        init() {
            var _a, _b;
            const filters = {
                startDate: document.getElementById('start-date'),
                endDate: document.getElementById('end-date'),
                searchBar: document.getElementById('search-bar'),
                monthFilter: document.getElementById('month-filter'),
                townFilter: document.getElementById('town-filter'),
                typeButtons: document.querySelectorAll('#post-type-filters .post-type-filter'),
                resetBtn: document.getElementById('reset-filters')
            };
            initFilters(filters, () => {
                this.currentPage = 1;
                this.load();
            });
            (_a = document.getElementById('prev-page')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.load();
                }
            });
            (_a = document.getElementById('prev-page')) === null || _a === void 0 ? void 0 : _a.addEventListener('keydown', e => {
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    document.getElementById('next-page')?.focus();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    (_a).click();
                }
            });
            (_b = document.getElementById('next-page')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', () => {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.load();
                }
            });
            (_b = document.getElementById('next-page')) === null || _b === void 0 ? void 0 : _b.addEventListener('keydown', e => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    document.getElementById('prev-page')?.focus();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    (_b).click();
                }
            });
            observeLoad(() => this.load());
        }
        load() {
            return __awaiter(this, void 0, void 0, function* () {
                const skel = document.getElementById('vg-events-skeleton');
                const container = document.getElementById('elementor-loop-content');
                if (skel)
                    skel.style.display = 'block';
                if (container) {
                    container.classList.remove('loaded');
                    container.setAttribute('aria-busy', 'true');
                }
                const active = document.querySelector('#post-type-filters .post-type-filter.active');
                const params = new URLSearchParams({
                    post_types: this.config.post_types.join(','),
                    selected_post_type: active ? active.dataset.postType || '' : '',
                    start_date: document.getElementById('start-date').value,
                    end_date: document.getElementById('end-date').value,
                    search: document.getElementById('search-bar').value,
                    month: document.getElementById('month-filter').value,
                    town: document.getElementById('town-filter').value,
                    template_id: String(this.config.template_id),
                    paged: String(this.currentPage),
                    _wpnonce: this.config.nonce,
                    prefetch_next: '1'
                });
                const url = `${this.config.rest_url}?${params.toString()}`;
                const data = yield fetchEvents(url);
                if (container) {
                    container.innerHTML = data.content || '';
                    container.classList.add('loaded');
                    container.setAttribute('aria-busy', 'false');
                    const first = container.querySelector('.vg-event-title');
                    if (first) {
                        first.setAttribute('tabindex', '-1');
                        first.focus();
                    }
                }
                if (skel)
                    skel.style.display = 'none';
                this.totalPages = data.total_pages || 1;
                this.currentPage = data.current_page || 1;
                updatePagination(this.currentPage, this.totalPages);
                if (data.next_page) {
                    const next = `${this.config.rest_url}?paged=${this.currentPage + 1}`;
                    prefetchNext(next);
                }
                const month = document.getElementById('month-filter').value;
                const town = document.getElementById('town-filter').value;
                let msg = `${data.total_posts || 0} events`;
                if (month)
                    msg += ` for ${month}`;
                if (town)
                    msg += ` in ${town}`;
                announce('vg-aria-announcer', msg);
                if (this.config.debug && data.debug) {
                    const panel = document.getElementById('vg-events-debug');
                    if (panel) {
                        panel.classList.add('active');
                        panel.textContent = JSON.stringify(data.debug, null, 2);
                    }
                }
            });
        }
    }
    function init() {
        if (window.vgEvents) {
            new Calendar(window.vgEvents);
        }
    }
    init();

    return init;

})();
