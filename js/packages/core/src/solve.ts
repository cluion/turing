import { handleRequest } from './worker-protocol';

/** Options for a worker-offloaded solve. */
export interface SolveOptions {
  signal?: AbortSignal;
}

/**
 * Solve the PoW in a same-origin module worker to keep the main thread free.
 * Falls back to the inline solver when:
 * - Worker is unavailable,
 * - the bundler left import.meta.url empty (IIFE/CDN global build),
 * - Worker construction fails, or
 * - the worker script errors (404 / CSP / module load).
 *
 * CDN and Laravel demos that only serve turing.global.js must still succeed.
 */
export async function solveInWorker(algorithm: string, params: unknown, opts: SolveOptions = {}): Promise<number> {
  const inline = async (): Promise<number> => {
    const res = await handleRequest({ algorithm, params }, opts.signal);
    if (res.ok) return res.counter as number;
    throw new Error(res.error);
  };

  if (typeof Worker === 'undefined') {
    return inline();
  }

  // tsup IIFE warns: import.meta is empty — new URL('./worker.js', '') is useless.
  const metaUrl = typeof import.meta !== 'undefined' && import.meta.url ? import.meta.url : '';
  if (!metaUrl) {
    return inline();
  }

  let workerUrl: URL;
  try {
    workerUrl = new URL('./worker.js', metaUrl);
  } catch {
    return inline();
  }

  let worker: Worker;
  try {
    worker = new Worker(workerUrl, { type: 'module' });
  } catch {
    return inline();
  }

  try {
    return await new Promise<number>((resolve, reject) => {
      const abort = () => {
        worker.terminate();
        reject(new Error('aborted'));
      };
      opts.signal?.addEventListener('abort', abort, { once: true });
      worker.onmessage = (e: MessageEvent) => {
        const res = e.data as { ok: boolean; counter?: number; error?: string };
        res.ok ? resolve(res.counter as number) : reject(new Error(res.error ?? 'worker solve failed'));
      };
      worker.onerror = () => reject(new Error('worker failed'));
      worker.postMessage({ algorithm, params });
    });
  } catch (err) {
    // Script 404 / CSP / broken module graph → finish on the main thread.
    if (err instanceof Error && (err.message === 'worker failed' || err.message === 'aborted')) {
      if (err.message === 'aborted') throw err;
      return inline();
    }
    throw err;
  } finally {
    worker.terminate();
  }
}
