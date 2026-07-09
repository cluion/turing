import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Turing } from '../src/index';

describe('Turing (react)', () => {
  it('renders turing-captcha with mapped attributes', () => {
    const { container } = render(<Turing url="/turing/challenge" type="pow" />);
    const el = container.querySelector('turing-captcha')!;
    expect(el.getAttribute('url')).toBe('/turing/challenge');
    expect(el.getAttribute('type')).toBe('pow');
  });

  it('calls onSolved when the element dispatches turing:solved', () => {
    const onSolved = vi.fn();
    const { container } = render(<Turing url="/turing/challenge" onSolved={onSolved} />);
    container.querySelector('turing-captcha')!.dispatchEvent(new CustomEvent('turing:solved', { bubbles: true }));
    expect(onSolved).toHaveBeenCalled();
  });
});
