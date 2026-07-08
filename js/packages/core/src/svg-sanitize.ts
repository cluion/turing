// Elements allowed inside a rendered challenge SVG. The server's SvgRenderer
// emits only svg/g/rect/circle/line/text; the wider set below covers ordinary
// presentation markup. Anything not listed (script, style, foreignObject, use,
// image, animation and link elements) is stripped before the node goes live.
const ALLOWED_ELEMENTS = new Set([
  'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
  'polygon', 'text', 'tspan', 'defs', 'title', 'desc',
  'lineargradient', 'radialgradient', 'stop',
]);

// Matches a url(...) reference to anything other than a local #fragment, e.g.
// url(http://evil/x), url('//evil'), url( data:... ). Presentation attributes
// (fill, stroke, filter, mask, clip-path, marker-*, cursor) and style accept
// these and would fetch the external resource on render.
const EXTERNAL_URL_REF = /url\(\s*['"]?\s*(?!#)/i;

/**
 * Strip anything that could execute script or load external content from a
 * parsed SVG tree, mutating it in place before it enters the live DOM:
 * disallowed elements are removed, every on* event-handler attribute is
 * dropped, href/xlink:href/src values that are not local #fragments are
 * removed, and any attribute carrying an external url(...) reference (fill,
 * style, filter, ...) is removed. This is defense in depth that does not rely
 * on the embedding page's CSP -- inline event handlers execute on append
 * regardless of how a node was inserted, so avoiding innerHTML alone is not
 * enough, and a url(http://...) fetches without any user interaction.
 */
export function sanitizeSvg(root: Element): void {
  const elements = [root, ...Array.from(root.querySelectorAll('*'))];
  for (const el of elements) {
    if (!ALLOWED_ELEMENTS.has(el.localName.toLowerCase())) {
      el.remove();
      continue;
    }
    for (const attr of Array.from(el.attributes)) {
      const name = attr.name.toLowerCase();
      if (name.startsWith('on')) {
        el.removeAttribute(attr.name);
        continue;
      }
      const isRef = name === 'href' || name === 'src' || name.endsWith(':href');
      if (isRef && !attr.value.trim().startsWith('#')) {
        el.removeAttribute(attr.name);
        continue;
      }
      if (EXTERNAL_URL_REF.test(attr.value)) {
        el.removeAttribute(attr.name);
      }
    }
  }
}
