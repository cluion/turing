# 純 HTML

不需要打包工具——從 CDN 載入瀏覽器 global,在表單裡放一個 `[data-turing]` 容器。

## 加上 script 與容器

pin 明確版本並加上 Subresource Integrity,避免 CDN 被動手腳把 script 換掉:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.0/dist/turing.global.js"
  integrity="sha384-CUAGKKDGZDcu6hnrOgnpoNw7eLOC9QTfaUAeLTI4OoN+Xnb2kgGacoBZQkELpCy0"
  crossorigin="anonymous"
  defer></script>

<form method="post" action="/submit">
  <div data-turing data-turing-url="/turing/challenge" data-turing-type="pow"></div>
  <button type="submit">送出</button>
</form>
```

在 `DOMContentLoaded` 時,widget 會找出每個 `[data-turing]` 容器、讀取
`data-turing-url`(以及可選的 `data-turing-type` / `data-turing-field`)、抓取並
解開 challenge、再把一個隱藏的 `turing_token` input 加進所在的 `<form>`。也可以用
`window.Turing.mount(el)` 手動控制。

發布後產生 `sha384` 雜湊:

```bash
curl -s https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.1.0/dist/turing.global.js \
  | openssl dgst -sha384 -binary | openssl base64 -A | sed 's/^/sha384-/'
```

## 打包工具的 app

如果用 Vite/webpack,改成從 npm 安裝——import 這個套件就會自動掛載每個
`[data-turing]` 容器:

```bash
pnpm add @cluion/turing-core
```

```js
import '@cluion/turing-core';
```

完整的 `data-turing-*` 清單見[屬性與 props](/zh/reference/attributes)。
