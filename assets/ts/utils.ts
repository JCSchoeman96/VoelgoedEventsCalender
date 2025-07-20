export function debounce<T extends (...args: any[]) => void>(fn: T, wait: number): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout> | undefined;
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn(...args), wait);
  };
}

export function supportsSession(): boolean {
  try {
    const key = '__test';
    sessionStorage.setItem(key, '1');
    sessionStorage.removeItem(key);
    return true;
  } catch {
    return false;
  }
}

export function getCache<T>(key: string): T | null {
  if (supportsSession()) {
    const item = sessionStorage.getItem(key);
    if (item) {
      try {
        const parsed = JSON.parse(item);
        if (!parsed.exp || parsed.exp > Date.now()) {
          return parsed.value as T;
        }
      } catch {
        return null;
      }
    }
  }
  return null;
}

export function setCache(key: string, value: unknown, ttl = 300000): void {
  if (supportsSession()) {
    const payload = { value, exp: Date.now() + ttl };
    try {
      sessionStorage.setItem(key, JSON.stringify(payload));
    } catch {
      /* noop */
    }
  }
}

export function supportsDateInput(): boolean {
  const input = document.createElement('input');
  input.setAttribute('type', 'date');
  const value = 'not-a-date';
  input.setAttribute('value', value);
  return input.value !== value;
}
