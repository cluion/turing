import { defineConfig } from 'vitepress';

// Static docs for Turing: integration guides, the wire-contract reference bound
// to the frozen vectors, and a live in-browser demo. Bilingual (en / zh-TW) via
// VitePress locales — the language dropdown appears automatically.
export default defineConfig({
  // GitHub Pages serves this as a project site under /turing/; the deploy
  // workflow sets DOCS_BASE. Local dev/build stays at '/'.
  base: process.env.DOCS_BASE || '/',
  title: 'Turing',
  description: 'Self-hosted, zero-dependency, cross-language modern captcha.',
  themeConfig: {
    outline: 'deep',
  },
  locales: {
    root: {
      label: 'English',
      lang: 'en',
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
              { text: 'Migrating', link: '/guide/migrating' },
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
      },
    },
    zh: {
      label: '繁體中文',
      lang: 'zh-TW',
      link: '/zh/',
      themeConfig: {
        nav: [
          { text: '指南', link: '/zh/guide/laravel' },
          { text: '參考', link: '/zh/reference/wire-contract' },
          { text: '示範', link: '/zh/demo' },
        ],
        sidebar: [
          {
            text: '指南',
            items: [
              { text: 'Laravel', link: '/zh/guide/laravel' },
              { text: '遷移指南', link: '/zh/guide/migrating' },
              { text: '純 HTML', link: '/zh/guide/plain-html' },
              { text: 'Vue', link: '/zh/guide/vue' },
              { text: 'React', link: '/zh/guide/react' },
            ],
          },
          {
            text: '參考',
            items: [
              { text: 'Wire contract（線上協定）', link: '/zh/reference/wire-contract' },
              { text: '屬性與 props', link: '/zh/reference/attributes' },
            ],
          },
          { text: '線上示範', link: '/zh/demo' },
        ],
        docFooter: { prev: '上一頁', next: '下一頁' },
        outlineTitle: '本頁內容',
        returnToTopLabel: '回到頂部',
        langMenuLabel: '切換語言',
      },
    },
  },
});
