import { fetchEvents } from './api.js';
import { initFilters } from './filters.js';
import { updatePagination } from './pagination.js';
import { prefetchNext } from './prefetch.js';
import { announce } from './aria.js';

interface Config {
  rest_url: string;
  post_types: string[];
  template_id: number;
  nonce: string;
  debug: boolean;
}

declare global {
  interface Window { vgEvents: Config; }
}

class Calendar {
  private currentPage = 1;
  private totalPages = 1;
  private config: Config;

  constructor(cfg: Config) {
    this.config = cfg;
    document.addEventListener('DOMContentLoaded', () => this.init());
  }

  private init(): void {
    const filters = {
      startDate: document.getElementById('start-date') as HTMLInputElement,
      endDate: document.getElementById('end-date') as HTMLInputElement,
      searchBar: document.getElementById('search-bar') as HTMLInputElement,
      monthFilter: document.getElementById('month-filter') as HTMLSelectElement,
      townFilter: document.getElementById('town-filter') as HTMLSelectElement,
      typeButtons: document.querySelectorAll('#post-type-filters .post-type-filter') as NodeListOf<HTMLElement>,
      resetBtn: document.getElementById('reset-filters') as HTMLElement
    };
    initFilters(filters, () => {
      this.currentPage = 1;
      this.load();
    });
    document.getElementById('prev-page')?.addEventListener('click', () => {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.load();
      }
    });
    document.getElementById('next-page')?.addEventListener('click', () => {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.load();
      }
    });
    this.load();
  }

  private async load(): Promise<void> {
    const active = document.querySelector('#post-type-filters .post-type-filter.active');
    const params = new URLSearchParams({
      post_types: this.config.post_types.join(','),
      selected_post_type: active ? (active as HTMLElement).dataset.postType || '' : '',
      start_date: (document.getElementById('start-date') as HTMLInputElement).value,
      end_date: (document.getElementById('end-date') as HTMLInputElement).value,
      search: (document.getElementById('search-bar') as HTMLInputElement).value,
      month: (document.getElementById('month-filter') as HTMLSelectElement).value,
      town: (document.getElementById('town-filter') as HTMLSelectElement).value,
      template_id: String(this.config.template_id),
      paged: String(this.currentPage),
      _wpnonce: this.config.nonce,
      prefetch_next: '1'
    });
    const url = `${this.config.rest_url}?${params.toString()}`;
    const data = await fetchEvents(url);
    const container = document.getElementById('elementor-loop-content');
    if (container) {
      container.innerHTML = data.content || '';
    }
    this.totalPages = data.total_pages || 1;
    this.currentPage = data.current_page || 1;
    updatePagination(this.currentPage, this.totalPages);
    if (data.next_page) {
      const next = `${this.config.rest_url}?paged=${this.currentPage + 1}`;
      prefetchNext(next);
    }
    announce('vg-aria-announcer', `${data.schema ? data.schema.length : 0} events`);
    if (this.config.debug && data.debug) {
      const panel = document.getElementById('vg-events-debug');
      if (panel) {
        panel.classList.add('active');
        panel.textContent = JSON.stringify(data.debug, null, 2);
      }
    }
  }
}

export default function init(): void {
  if (window.vgEvents) {
    new Calendar(window.vgEvents);
  }
}

init();
