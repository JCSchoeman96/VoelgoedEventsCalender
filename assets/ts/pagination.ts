import { announce } from './aria.js';

export function updatePagination(current: number, total: number): void {
  const info = document.getElementById('page-info');
  const prev = document.getElementById('prev-page') as HTMLButtonElement;
  const next = document.getElementById('next-page') as HTMLButtonElement;
  if (info) info.textContent = `${current} / ${total}`;
  if (prev) prev.disabled = current === 1;
  if (next) next.disabled = current === total;
  announce('vg-aria-announcer', `${current} of ${total}`);
}
