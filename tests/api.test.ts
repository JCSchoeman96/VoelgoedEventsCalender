import { fetchEvents } from '../assets/ts/api';

global.fetch = jest.fn(() => Promise.resolve({ json: () => Promise.resolve({ ok: true }) })) as any;

test('fetchEvents returns data', async () => {
  const data = await fetchEvents('http://example.com');
  expect(data.ok).toBe(true);
});
