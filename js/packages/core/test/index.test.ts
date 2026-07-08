import { describe, expect, it, vi } from 'vitest';

describe('index side-effect', () => {
  it('auto-mounts existing [data-turing] containers on import', async () => {
    const challenge = { token: 't', image: null, type: 'unknown', params: null, expires: 1 };
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify(challenge), { status: 200 }));

    const el = document.createElement('div');
    el.setAttribute('data-turing', '');
    el.setAttribute('data-turing-url', 'http://localhost/turing/challenge');
    document.body.appendChild(el);

    // Importing the entry triggers autoMount() because the document is ready.
    await import('../src/index');
    await new Promise((r) => setTimeout(r, 0));

    // fetch was invoked for the container (unknown type is a no-op after fetch).
    expect(globalThis.fetch).toHaveBeenCalled();
    vi.restoreAllMocks();
  });
});
