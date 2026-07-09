import { handleRequest } from './worker-protocol';

/** Options for a worker-offloaded solve. */
export interface SolveOptions {
  signal?: AbortSignal;
}

/**
 * Solve the PoW in a same-origin module worker to keep the main thread free.
 * Falls back to the inline solver when Worker (or the bundler URL) is
 * unavailable, so the CDN/IIFE build and older setups still work.
 */
export async function solveInWorker(algorithm: string, params: unknown, opts: SolveOptions = {}): Promise<number> {
  if (typeof Worker === 'undefined') {
    const res = await handleRequest({ algorithm, params }, opts.signal);
    if (res.ok) return res.counter;
    throw new Error(res.error);
  }
  const worker = new Worker(new URL('./worker.js', import.meta.url), { type: 'module' });
  try {
    return await new Promise<number>((resolve, reject) => {
      const abort = () => {
        worker.terminate();
        reject(new Error('aborted'));
      };
      opts.signal?.addEventListener('abort', abort, { once: true });
      worker.onmessage = (e: MessageEvent) => {
        const res = e.data as { ok: boolean; counter?: number; error?: string };
        res.ok ? resolve(res.counter as number) : reject(new Error(res.error));
      };
      worker.onerror = () => reject(new Error('worker failed'));
      worker.postMessage({ algorithm, params });
    });
  } finally {
    worker.terminate();
  }
}
