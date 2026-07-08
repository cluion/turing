import { describe, expect, it } from 'vitest';
import { sanitizeSvg } from '../src/svg-sanitize';

/**
 * Parse markup the same way mount.ts does (text/html) and return its svg root.
 */
function parseSvg(markup: string): Element {
  const doc = new DOMParser().parseFromString(markup, 'text/html');
  return doc.querySelector('svg')!;
}

describe('sanitizeSvg', () => {
  it('drops on* event-handler attributes on the root and descendants', () => {
    const svg = parseSvg('<svg xmlns="http://www.w3.org/2000/svg" onload="x()"><rect onclick="y()"/></svg>');
    sanitizeSvg(svg);
    expect(svg.hasAttribute('onload')).toBe(false);
    expect(svg.querySelector('rect')?.hasAttribute('onclick')).toBe(false);
  });

  it('removes script, style, foreignObject, use and image elements', () => {
    const svg = parseSvg(
      '<svg xmlns="http://www.w3.org/2000/svg">' +
        '<script>bad()</script><style>@import url(x)</style>' +
        '<foreignObject><img src="x" onerror="z()"></foreignObject>' +
        '<use href="http://evil/a.svg#x"/><image href="http://evil/beacon"/>' +
        '<rect/></svg>',
    );
    sanitizeSvg(svg);
    expect(svg.querySelector('script')).toBeNull();
    expect(svg.querySelector('style')).toBeNull();
    expect(svg.querySelector('foreignObject')).toBeNull();
    expect(svg.querySelector('use')).toBeNull();
    expect(svg.querySelector('image')).toBeNull();
    expect(svg.querySelector('img')).toBeNull();
    // legit content survives
    expect(svg.querySelector('rect')).not.toBeNull();
  });

  it('keeps the elements the server actually renders', () => {
    const svg = parseSvg(
      '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/><circle/><line/><text>7</text></g></svg>',
    );
    sanitizeSvg(svg);
    expect(svg.querySelector('g')).not.toBeNull();
    expect(svg.querySelector('rect')).not.toBeNull();
    expect(svg.querySelector('circle')).not.toBeNull();
    expect(svg.querySelector('line')).not.toBeNull();
    expect(svg.querySelector('text')?.textContent).toBe('7');
  });

  it('strips external href but keeps local #fragment references', () => {
    const svg = parseSvg(
      '<svg xmlns="http://www.w3.org/2000/svg">' +
        '<lineargradient id="g" href="#base"/><rect href="http://evil/x"/></svg>',
    );
    sanitizeSvg(svg);
    expect(svg.querySelector('lineargradient')?.getAttribute('href')).toBe('#base');
    expect(svg.querySelector('rect')?.hasAttribute('href')).toBe(false);
  });

  it('strips external url() references in fill/style but keeps local ones and plain colors', () => {
    const svg = parseSvg(
      '<svg xmlns="http://www.w3.org/2000/svg">' +
        '<rect fill="url(http://evil/leak.svg#x)" stroke="#333"/>' +
        '<rect style="fill:url(http://evil/s.svg#y)"/>' +
        '<circle fill="url(#grad)"/></svg>',
    );
    sanitizeSvg(svg);
    const rects = svg.querySelectorAll('rect');
    expect(rects[0].hasAttribute('fill')).toBe(false);
    expect(rects[0].getAttribute('stroke')).toBe('#333');
    expect(rects[1].hasAttribute('style')).toBe(false);
    expect(svg.querySelector('circle')?.getAttribute('fill')).toBe('url(#grad)');
  });
});
