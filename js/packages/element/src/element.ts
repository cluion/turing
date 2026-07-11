import { mount } from '@cluion/turing-core';

/** Host attribute → data-turing-* on the inner container. */
const LABEL_ATTRS = [
  ['label', 'data-turing-label'],
  ['label-loading', 'data-turing-label-loading'],
  ['label-idle', 'data-turing-label-idle'],
  ['label-solving', 'data-turing-label-solving'],
  ['label-solved', 'data-turing-label-solved'],
  ['label-error', 'data-turing-label-error'],
  ['label-aria', 'data-turing-label-aria'],
  ['label-refresh', 'data-turing-label-refresh'],
] as const;

/**
 * <turing-captcha url="..." type="pow" field="turing_token"> — a custom element
 * that renders a light-DOM container, mounts the Turing core onto it, and
 * re-emits the outcome as turing:solved / turing:error events. Light DOM keeps
 * the injected hidden input inside the surrounding form so it is submitted.
 *
 * Optional: autostart, no-worker, label / label-idle / label-solving / …
 */
export class TuringCaptchaElement extends HTMLElement {
  /**
   * On connect, build (once) the [data-turing] container from this element's
   * attributes and run the core mount. Outcomes are re-emitted from core events
   * (PoW may wait for a checkbox click before turing:solved).
   */
  connectedCallback(): void {
    const url = this.getAttribute('url');
    if (!url) {
      this.dispatchError(new Error('turing-captcha requires a url attribute'));
      return;
    }
    const container = this.ensureContainer(url);

    // Re-emit core outcomes on the host element for Vue/React wrappers.
    container.addEventListener('turing:solved', () => {
      this.setAttribute('data-turing-state', 'solved');
      this.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
    });
    container.addEventListener('turing:error', ((e: Event) => {
      const detail = (e as CustomEvent).detail;
      this.dispatchError(detail?.error ?? new Error('turing error'));
    }) as EventListener);
    container.addEventListener('turing:ready', () => {
      this.setAttribute('data-turing-state', 'ready');
      this.dispatchEvent(new CustomEvent('turing:ready', { bubbles: true }));
    });

    mount(container).catch((error: unknown) => this.dispatchError(error));
  }

  /**
   * Create the child container mapping url/type/field to the core's data-*
   * attributes, or reuse the existing one on reconnect.
   */
  private ensureContainer(url: string): HTMLElement {
    let container = this.querySelector<HTMLElement>(':scope > [data-turing]');
    if (!container) {
      container = document.createElement('div');
      container.setAttribute('data-turing', '');
      this.appendChild(container);
    }
    container.setAttribute('data-turing-url', url);
    const type = this.getAttribute('type');
    if (type) container.setAttribute('data-turing-type', type);
    else container.removeAttribute('data-turing-type');
    const field = this.getAttribute('field');
    if (field) container.setAttribute('data-turing-field', field);
    else container.removeAttribute('data-turing-field');

    // Boolean attrs on the host → data-* on the container.
    if (this.hasAttribute('autostart')) {
      container.setAttribute('data-turing-autostart', '');
    } else {
      container.removeAttribute('data-turing-autostart');
    }
    if (this.hasAttribute('no-worker')) {
      container.setAttribute('data-turing-no-worker', '');
    } else {
      container.removeAttribute('data-turing-no-worker');
    }
    if (this.hasAttribute('no-refresh')) {
      container.setAttribute('data-turing-no-refresh', '');
    } else {
      container.removeAttribute('data-turing-no-refresh');
    }

    for (const [hostAttr, dataAttr] of LABEL_ATTRS) {
      const v = this.getAttribute(hostAttr);
      if (v !== null && v !== '') {
        container.setAttribute(dataAttr, v);
      } else {
        container.removeAttribute(dataAttr);
      }
    }

    return container;
  }

  /**
   * Reflect a failure as data-turing-state="error" and a bubbling event.
   */
  private dispatchError(error: unknown): void {
    this.setAttribute('data-turing-state', 'error');
    this.dispatchEvent(new CustomEvent('turing:error', { bubbles: true, detail: { error } }));
  }
}
