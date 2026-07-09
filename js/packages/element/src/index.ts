import { TuringCaptchaElement } from './element';

export { TuringCaptchaElement };

// Side effect: register the tag once. Importing the package (or loading the
// IIFE) is enough to make <turing-captcha> work.
if (typeof customElements !== 'undefined' && !customElements.get('turing-captcha')) {
  customElements.define('turing-captcha', TuringCaptchaElement);
}
