import '@cluion/turing-element';
import { createElement, useEffect, useRef } from 'react';

/** Props for the React <Turing/> wrapper. */
export interface TuringProps {
  url: string;
  type?: string;
  field?: string;
  /** Solve PoW immediately without a checkbox. */
  autostart?: boolean;
  /** Force main-thread PoW (disable Web Worker). */
  noWorker?: boolean;
  onSolved?: () => void;
  onError?: (error: unknown) => void;
  onReady?: () => void;
}

/**
 * Renders the turing-captcha custom element and wires its events to callback
 * props via a ref effect (portable across React 18/19).
 */
export function Turing({ url, type, field, autostart, noWorker, onSolved, onError, onReady }: TuringProps) {
  const ref = useRef<HTMLElement>(null);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const solved = () => onSolved?.();
    const errored = (e: Event) => onError?.((e as CustomEvent).detail?.error);
    const ready = () => onReady?.();
    el.addEventListener('turing:solved', solved);
    el.addEventListener('turing:error', errored);
    el.addEventListener('turing:ready', ready);
    return () => {
      el.removeEventListener('turing:solved', solved);
      el.removeEventListener('turing:error', errored);
      el.removeEventListener('turing:ready', ready);
    };
  }, [onSolved, onError, onReady]);
  return createElement('turing-captcha', {
    ref,
    url,
    type,
    field,
    ...(autostart ? { autostart: '' } : {}),
    ...(noWorker ? { 'no-worker': '' } : {}),
  });
}
