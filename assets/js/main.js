import { fetchEvents } from './modules/api.js';
import { initFilters } from './modules/filters.js';
import { initPagination } from './modules/pagination.js';
import { showSkeleton } from './modules/skeleton.js';

export default function init() {
  initFilters();
  initPagination();
  // other init code
}

export { fetchEvents, showSkeleton };
