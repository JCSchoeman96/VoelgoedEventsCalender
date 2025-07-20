import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import typescript from '@rollup/plugin-typescript';
import { terser } from 'rollup-plugin-terser';

const config = {
  input: 'assets/ts/main.ts',
  output: [
    { file: 'assets/js/events-calendar.js', format: 'iife', name: 'VGCalendar' },
    { file: 'assets/js/events-calendar.min.js', format: 'iife', name: 'VGCalendar', plugins: [terser()] }
  ],
  plugins: [nodeResolve(), commonjs(), typescript({ tsconfig: './tsconfig.json' })]
};

export default config;
