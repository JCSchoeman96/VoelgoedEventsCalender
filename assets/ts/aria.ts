export function setPressed(el: HTMLElement, pressed: boolean): void {
  el.setAttribute('aria-pressed', pressed ? 'true' : 'false');
}

export function announce(id: string, msg: string): void {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = msg;
  }
}
