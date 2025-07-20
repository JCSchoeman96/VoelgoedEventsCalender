import { getCache, setCache } from './utils';

export async function fetchEvents(url: string): Promise<any> {
  const cached = getCache<any>(url);
  if (cached) {
    return cached;
  }
  const res = await fetch(url);
  const data = await res.json();
  setCache(url, data, 300000);
  return data;
}
