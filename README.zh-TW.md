# Turing

[![CI](https://github.com/cluion/turing/actions/workflows/ci.yml/badge.svg)](https://github.com/cluion/turing/actions/workflows/ci.yml)
[![npm](https://img.shields.io/npm/v/@cluion/turing-core?label=npm%20%40cluion%2Fturing-core)](https://www.npmjs.com/package/@cluion/turing-core)
[![Packagist](https://img.shields.io/packagist/v/cluion/turing?label=packagist%20cluion%2Fturing)](https://packagist.org/packages/cluion/turing)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](#授權)

[English](README.md) · **繁體中文** · 📖 [文件站](https://cluion.github.io/turing/)

伺服器發出一個已簽章的 challenge、前端 widget 用瀏覽器原生 Web Crypto 解開它、
再把 token 交給所在表單送回伺服器驗證。不呼叫任何第三方服務、不追蹤。三層架構:
與框架無關的 **PHP Core**、框架**整合**(Laravel 優先),以及 **JS 前端**套件。

## 安裝

### Laravel(PHP)

```bash
composer require cluion/turing
```

```bash
php artisan vendor:publish --tag=turing-config   # 接著設定 TURING_SECRET
```

### JavaScript

| 套件 | 安裝 | 用途 |
|------|------|------|
| [`@cluion/turing-core`](https://www.npmjs.com/package/@cluion/turing-core) | `pnpm add @cluion/turing-core` | Headless 核心(純 HTML / 任何框架) |
| [`@cluion/turing-element`](https://www.npmjs.com/package/@cluion/turing-element) | `pnpm add @cluion/turing-element` | `<turing-captcha>` Web Component |
| [`@cluion/turing-vue`](https://www.npmjs.com/package/@cluion/turing-vue) | `pnpm add @cluion/turing-vue` | `<Turing>` Vue 3 元件 |
| [`@cluion/turing-react`](https://www.npmjs.com/package/@cluion/turing-react) | `pnpm add @cluion/turing-react` | `<Turing/>` React 元件 |

純 HTML 走 CDN —— pin 明確版本並加上 Subresource Integrity(避免 CDN 被動手腳):

```html
<script
  src="https://cdn.jsdelivr.net/npm/@cluion/turing-core@0.2.0/dist/turing.global.js"
  integrity="sha384-mnsCwwvqQfqd7zQDpBAmT0Gj0sUWwYxYjmONH7/kiDmAZEvlg03ujfhxPqCUEFRV"
  crossorigin="anonymous"
  defer></script>
```

## 使用

### Laravel —— 一行顯示、一行驗證

```blade
<form method="post" action="/submit">
  @csrf
  <x-turing type="pow" />
  <button type="submit">送出</button>
</form>
```

```php
$request->validate(['turing_token' => 'required|turing']);
// 或:Turing::verifyRequest($request);
```

`<x-turing/>` 元件渲染一個 CSP 安全的容器,前端 widget 掛載其上。widget 會從
`data-turing-url` 抓 challenge、用瀏覽器原生 Web Crypto 解 PoW(不需 WASM),
再注入隱藏的 `turing_token` 讓表單送出。

### JavaScript

```js
import '@cluion/turing-core'; // 自動掛載每個 [data-turing] 容器
```

可直接開來跑的純 HTML 範例在
[`examples/plain-html/index.html`](examples/plain-html/index.html)。各框架指南:
[Laravel](docs/guide/laravel.md) · [純 HTML](docs/guide/plain-html.md) ·
[Vue](docs/guide/vue.md) · [React](docs/guide/react.md)。

## Core

與框架無關的 PHP 核心位於 `php/src/Core`。Token =
`base64url(payload).base64url(signature)`,payload 用 canonical(key 遞迴排序)
JSON。預設 HMAC-SHA256 簽章(Ed25519 選用)。Challenge 類型:`math`、`text`、
`pow`(預設 PBKDF2-SHA256,SHA-256 leading-zero-bit 選用)。預設無狀態;透過
`Store` 可做一次性(single-use)。

## 跨語言 vectors

[`php/tests/vectors/`](php/tests/vectors/) 是 wire contract(線上協定)——權威的
[wire-contract 參考](docs/reference/wire-contract.md)逐字引用它們(docs 建置時有
漂移檢查,不一致會失敗)。任何語言移植版**必須**逐位元重現這些 fixtures(token
位元組、PoW counter、答案雜湊)。二進位 payload 欄位(如 `keySignature`)一律是
原始位元組的 base64url。

## 開發

```bash
composer install
vendor/bin/phpunit                 # PHP:Core + Laravel 測試(CI 跑 Laravel 10/11/12)

cd js && pnpm install
pnpm -r build && pnpm -r test      # JS:4 個套件(先 build 再 test)
pnpm -r typecheck

cd ../docs && pnpm docs:build      # 文件站 + vector 漂移檢查
cd ../e2e  && pnpm exec playwright test   # 瀏覽器 round-trip(需要 chromium)
```

## 授權

MIT。
