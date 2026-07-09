import '@cluion/turing-element';
import { createElement, useEffect, useRef } from 'react';

/** Props for the React <Turing/> wrapper. */
export interface TuringProps {
  url: string;
  type?: string;
  field?: string;
  onSolved?: () => void;
  onError?: (error: unknown) => void;
}

/**
 * Renders the turing-captcha custom element and wires its events to callback
 * props via a ref effect (portable across React 18/19). Behavior lives in the
 * element; this only maps props and events.
 */
export function Turing({ url, type, field, onSolved, onError }: TuringProps) {
  const ref = useRef<HTMLElement>(null);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const solved = () => onSolved?.();
    const errored = (e: Event) => onError?.((e as CustomEvent).detail?.error);
    el.addEventListener('turing:solved', solved);
    el.addEventListener('turing:error', errored);
    return () => {
      el.removeEventListener('turing:solved', solved);
      el.removeEventListener('turing:error', errored);
    };
  }, [onSolved, onError]);
  // Lowercase custom-element tag; map optional attrs only when present.
  return createElement('turing-captcha', { ref, url, type, field });
}
