import { debounce, supportsDateInput } from './utils.js';
import { setPressed } from './aria.js';

export interface Filters {
  startDate: HTMLInputElement;
  endDate: HTMLInputElement;
  searchBar: HTMLInputElement;
  monthFilter: HTMLSelectElement;
  townFilter: HTMLSelectElement;
  typeButtons: NodeListOf<HTMLElement>;
  resetBtn: HTMLElement;
}

export function initFilters(filters: Filters, onChange: () => void): void {
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
