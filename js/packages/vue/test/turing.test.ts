import { mount as vtuMount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Turing from '../src/index';

describe('Turing (vue)', () => {
  it('renders turing-captcha and forwards url/type', () => {
    const wrapper = vtuMount(Turing, { props: { url: '/turing/challenge', type: 'pow' } });
    const el = wrapper.element as HTMLElement;
    expect(el.tagName.toLowerCase()).toBe('turing-captcha');
    expect(el.getAttribute('url')).toBe('/turing/challenge');
    expect(el.getAttribute('type')).toBe('pow');
  });

  it('emits solved when the element dispatches turing:solved', async () => {
    const wrapper = vtuMount(Turing, { props: { url: '/turing/challenge' } });
    wrapper.element.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
    expect(wrapper.emitted('solved')).toBeTruthy();
  });
});
