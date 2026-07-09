import { handleRequest, type SolveRequest } from './worker-protocol';

// Worker global message loop: receive one SolveRequest, post one SolveResponse.
self.addEventListener('message', async (event: MessageEvent<SolveRequest>) => {
  const res = await handleRequest(event.data);
  (self as unknown as Worker).postMessage(res);
});
