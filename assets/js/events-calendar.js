class VGEventsCalendar {
  constructor(config) {
    this.config = config;
    this.currentPage = 1;
    this.totalPages = 1;
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
      el.addEventListener('click', () => {
        document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        this.currentPage = 1;
        this.triggerLoad();
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
      document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => i.classList.remove('active'));
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
    container.innerHTML = '<p>Loading...</p>';
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
        _wpnonce: this.config.nonce
      });
      const url = this.config.rest_url + '?' + params.toString();
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
      container.innerHTML = data.content || '<p>No posts found.</p>';
      this.totalPages = data.total_pages || 1;
      this.currentPage = data.current_page || 1;
      this.updatePagination();
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
      container.innerHTML = '<p>Error loading content. Please try again.</p>';
    } finally {
      if (this.spinner) this.spinner.style.display = 'none';
    }
  }

  updatePagination() {
    document.getElementById('page-info').textContent = `${this.currentPage} van ${this.totalPages}`;
    document.getElementById('prev-page').disabled = this.currentPage === 1;
    document.getElementById('next-page').disabled = this.currentPage === this.totalPages;
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
