class VGEventsCalendar {
  constructor(config) {
    this.config = config;
    this.currentPage = 1;
    this.totalPages = 1;
    this.prefetchLink = null;
    this.cacheEls();
    this.init();
  }

  cacheEls() {
    this.startDate = document.getElementById('start-date');
    this.endDate = document.getElementById('end-date');
    this.searchBar = document.getElementById('search-bar');
    this.monthFilter = document.getElementById('month-filter');
    this.townFilter = document.getElementById('town-filter');
    this.spinner = document.getElementById('vg-events-spinner');
    this.announcer = document.getElementById('vg-aria-announcer');
  }

  init() {
    this.initFilters();
    this.triggerLoad();
  }

  supportsDateInput() {
    const input = document.createElement('input');
    input.setAttribute('type', 'date');
    const value = 'not-a-date';
    input.setAttribute('value', value);
    return input.value !== value;
  }

  loadFlatpickr() {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = this.config.flatpickr_css;
    document.head.appendChild(link);
    const script = document.createElement('script');
    script.src = this.config.flatpickr_js;
    script.onload = () => {
      if (window.flatpickr) {
        flatpickr(this.startDate, { dateFormat: 'Y-m-d' });
        flatpickr(this.endDate, { dateFormat: 'Y-m-d' });
      }
    };
    document.head.appendChild(script);
  }

  initFilters() {
    if (this.config.useDatepicker) {
      if (this.supportsDateInput()) {
        this.startDate.type = 'date';
        this.endDate.type = 'date';
      } else {
        this.loadFlatpickr();
      }
    }

    if (Array.isArray(this.config.towns)) {
      this.config.towns.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t;
        opt.textContent = t;
        this.townFilter.appendChild(opt);
      });
    }

    if (Array.isArray(this.config.months)) {
      this.config.months.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        this.monthFilter.appendChild(opt);
      });
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('town')) this.townFilter.value = params.get('town');
    if (params.get('month')) this.monthFilter.value = params.get('month');
    if (params.get('search')) this.searchBar.value = params.get('search');
    if (params.get('start')) this.startDate.value = params.get('start');
    if (params.get('end')) this.endDate.value = params.get('end');

    document.querySelectorAll('#post-type-filters .post-type-filter').forEach(el => {
      el.setAttribute('aria-pressed', 'false');
      const activate = () => {
        document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => {
          i.classList.remove('active');
          i.setAttribute('aria-pressed', 'false');
        });
        el.classList.add('active');
        el.setAttribute('aria-pressed', 'true');
        this.currentPage = 1;
        this.triggerLoad();
      };
      el.addEventListener('click', activate);
      el.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate();
        }
      });
    });

    const debounced = this.debounce(() => {
      this.currentPage = 1;
      this.triggerLoad();
    }, 300);

    [this.startDate, this.endDate, this.searchBar, this.monthFilter, this.townFilter].forEach(el => {
      el.addEventListener('input', debounced);
      el.addEventListener('change', debounced);
    });

    document.getElementById('prev-page').addEventListener('click', () => {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.triggerLoad();
      }
    });

    document.getElementById('next-page').addEventListener('click', () => {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.triggerLoad();
      }
    });

    document.getElementById('reset-filters').addEventListener('click', () => {
      this.searchBar.value = '';
      this.townFilter.value = '';
      this.monthFilter.value = '';
      this.startDate.value = '';
      this.endDate.value = '';
      document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => {
        i.classList.remove('active');
        i.setAttribute('aria-pressed', 'false');
      });
      this.currentPage = 1;
      this.loadResults();
      const msg = document.getElementById('filter-reset-msg');
      msg.style.display = 'block';
      setTimeout(() => { msg.style.display = 'none'; }, 2500);
    });
  }

  debounce(func, wait) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  async loadResults(postType = '', startDate = '', endDate = '', search = '', month = '', town = '') {
    const container = document.getElementById('elementor-loop-content');
    if (this.spinner) this.spinner.style.display = 'block';
    container.setAttribute('aria-busy', 'true');
    const skeleton = document.getElementById('vg-events-skeleton');
    if (skeleton) {
      skeleton.style.display = 'block';
      container.innerHTML = skeleton.innerHTML;
    } else {
      container.innerHTML = '<p>Loading...</p>';
    }
    try {
      const params = new URLSearchParams({
        post_types: this.config.post_types,
        selected_post_type: postType,
        start_date: startDate,
        end_date: endDate,
        search: search,
        month: month,
        town: town,
        template_id: this.config.template_id,
        paged: this.currentPage,
        _wpnonce: this.config.nonce,
        prefetch_next: 1
      });
      const url = this.config.rest_url + '?' + params.toString();
      window.dispatchEvent(new CustomEvent('vg_events_before_fetch', { detail: { params: params.toString() } }));
      const startTime = performance.now();
      if (this.config.debug) {
        console.log('[VG Events Plugin] Request URL:', url);
      }
      const response = await fetch(url);
      const data = await response.json();
      const endTime = performance.now();
      if (this.config.debug) {
        console.log('[VG Events Plugin] Response:', data);
        console.log(`[VG Events Plugin] Request time: ${Math.round(endTime - startTime)}ms`);
      }
      if (skeleton) skeleton.style.display = 'none';
      container.innerHTML = data.content || '<p>No posts found.</p>';
      if (data.next_page) {
        this.nextPageCache = data.next_page;
      }
      window.dispatchEvent(new CustomEvent('vg_events_after_fetch', { detail: { params: params.toString(), response: data } }));
      this.totalPages = data.total_pages || 1;
      this.currentPage = data.current_page || 1;
      this.updatePagination();
      this.updatePrefetch(params);
      if (this.announcer) {
        const count = Array.isArray(data.schema) ? data.schema.length : 0;
        let monthLabel = this.monthFilter.value;
        if (this.monthFilter.options && this.monthFilter.selectedIndex > 0) {
          monthLabel = this.monthFilter.options[this.monthFilter.selectedIndex].textContent;
        }
        this.announcer.textContent = `${count} events loaded${monthLabel ? ' for ' + monthLabel : ''}`;
      }
      if (this.config.debug && data.debug) {
        console.log('[VG Events Plugin] Debug info:', data.debug);
        const dbg = document.getElementById('vg-events-debug');
        if (dbg) {
          dbg.classList.add('active');
          dbg.textContent = JSON.stringify({ request: params.toString(), response: data.debug, timing: Math.round(endTime - startTime) + 'ms' }, null, 2);
        }
      } else {
        const dbg = document.getElementById('vg-events-debug');
        if (dbg) dbg.classList.remove('active');
      }
    } catch (err) {
      console.error(err);
      if (skeleton) skeleton.style.display = 'none';
      container.innerHTML = '<p>Error loading content. Please try again.</p>';
    } finally {
      if (this.spinner) this.spinner.style.display = 'none';
      container.setAttribute('aria-busy', 'false');
    }
  }

  updatePagination() {
    document.getElementById('page-info').textContent = `${this.currentPage} van ${this.totalPages}`;
    document.getElementById('prev-page').disabled = this.currentPage === 1;
    document.getElementById('next-page').disabled = this.currentPage === this.totalPages;
  }

  updatePrefetch(params) {
    const next = new URLSearchParams(params.toString());
    const nextPage = this.currentPage + 1;
    if (nextPage <= this.totalPages) {
      next.set('paged', nextPage);
      const href = this.config.rest_url + '?' + next.toString();
      if (!this.prefetchLink) {
        this.prefetchLink = document.createElement('link');
        this.prefetchLink.rel = 'prefetch';
        this.prefetchLink.id = 'vg-events-prefetch';
        document.head.appendChild(this.prefetchLink);
      }
      this.prefetchLink.href = href;
    } else if (this.prefetchLink) {
      this.prefetchLink.remove();
      this.prefetchLink = null;
    }
  }

  triggerLoad() {
    const active = document.querySelector('#post-type-filters .post-type-filter.active');
    const pt = active ? active.getAttribute('data-post-type') : '';
    this.loadResults(pt, this.startDate.value, this.endDate.value, this.searchBar.value, this.monthFilter.value, this.townFilter.value);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new VGEventsCalendar(window.vgEvents || {});
});
