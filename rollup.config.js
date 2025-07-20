import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import { terser } from 'rollup-plugin-terser';

export default {
  input: 'assets/js/main.js',
  output: {
    file: 'assets/js/events-calendar.min.js',
    format: 'iife',
    name: 'VGCalendar'
  },
  plugins: [nodeResolve(), commonjs(), terser()]
};
