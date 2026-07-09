import { defineConfig } from 'vitepress';

// Static docs for Turing: integration guides, the wire-contract reference bound
// to the frozen vectors, and a live in-browser demo.
export default defineConfig({
  title: 'Turing',
  description: 'Self-hosted, zero-dependency, cross-language modern captcha.',
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/laravel' },
      { text: 'Reference', link: '/reference/wire-contract' },
      { text: 'Demo', link: '/demo' },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Laravel', link: '/guide/laravel' },
          { text: 'Plain HTML', link: '/guide/plain-html' },
          { text: 'Vue', link: '/guide/vue' },
          { text: 'React', link: '/guide/react' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'Wire contract', link: '/reference/wire-contract' },
          { text: 'Attributes & props', link: '/reference/attributes' },
        ],
      },
      { text: 'Live demo', link: '/demo' },
    ],
    outline: 'deep',
  },
});
