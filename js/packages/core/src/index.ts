import { autoMount } from './mount';

export * from './encoding';
export * from './pow';
export * from './client';
export * from './mount';

// Side effect: auto-mount any [data-turing] containers once the DOM is ready.
// Importing '@cluion/turing-core' is enough to wire up Blade's <x-turing/>.
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => autoMount());
  } else {
    autoMount();
  }
}
