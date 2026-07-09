import { solvePbkdf2, solveShaBit, type Pbkdf2Params, type ShaBitParams } from './pow';

/** Request posted to the worker: which algorithm, and the challenge params. */
export interface SolveRequest {
  algorithm: string;
  params: unknown;
}

/** Response posted back: the counter, or a stringified error. */
export type SolveResponse = { ok: true; counter: number } | { ok: false; error: string };

/**
 * Pure request handler shared by the worker and the tests: dispatch to the
 * right solver and normalize success/failure into a SolveResponse.
 */
export async function handleRequest(req: SolveRequest, signal?: AbortSignal): Promise<SolveResponse> {
  try {
    const counter =
      req.algorithm === 'SHA-256'
        ? await solveShaBit(req.params as ShaBitParams, undefined, undefined, signal)
        : req.algorithm === 'PBKDF2-SHA256'
          ? await solvePbkdf2(req.params as Pbkdf2Params, undefined, undefined, signal)
          : (() => {
              throw new Error(`Unsupported PoW algorithm: ${req.algorithm}`);
            })();
    return { ok: true, counter };
  } catch (error) {
    return { ok: false, error: error instanceof Error ? error.message : String(error) };
  }
}
