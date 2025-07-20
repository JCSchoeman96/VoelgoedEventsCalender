import { debounce } from '../assets/ts/utils';

test('debounce returns a function', () => {
  const fn = jest.fn();
  const d = debounce(fn, 10);
  d();
  expect(typeof d).toBe('function');
});
