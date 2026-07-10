---
layout: home
hero:
  name: Turing
  text: 現代、自架的 captcha
  tagline: 零第三方相依、跨語言 wire contract、瀏覽器原生 Web Crypto proof-of-work。不呼叫外部服務、不追蹤。
  actions:
    - theme: brand
      text: 開始使用
      link: /zh/guide/laravel
    - theme: alt
      text: Wire contract
      link: /zh/reference/wire-contract
    - theme: alt
      text: 線上示範
      link: /zh/demo
features:
  - title: 自架
    details: 伺服器自己發出並驗證已簽章的 challenge。不呼叫第三方、沒有東西外洩、也沒有每次請求的配額。
  - title: 跨語言
    details: 一份凍結的 wire contract（token、canonical JSON、PoW），配上逐位元的 vectors,任何語言移植版都能重現。PHP core 先行。
  - title: Proof-of-work
    details: 決定性的 PBKDF2 或 hashcash SHA-bit,由瀏覽器原生 Web Crypto 解開——不需 WASM、CSP 安全,還可選擇丟到 Web Worker。
  - title: 隨插即用的前端
    details: 純 HTML 一個標籤、一個 &lt;turing-captcha&gt; Web Component,加上輕薄的 Vue / React 封裝,全都建構在同一個 headless 核心上。
---

## 這是什麼

Turing 是 cluion 的自架 captcha:伺服器發出一個已簽章的 challenge、前端 widget
在瀏覽器解開它、再把 token 交給所在表單送回伺服器驗證。整套與框架無關——PHP
**Core** 定義 wire contract、框架**整合**(Laravel 優先)把它接進請求流程、
**JS 前端**掛載 widget。

挑你的技術棧開始:

- [Laravel](/zh/guide/laravel) —— `<x-turing>` Blade 元件 + 驗證規則
- [純 HTML](/zh/guide/plain-html) —— 一個 `<script>` 加一個 `[data-turing]` 容器
- [Vue](/zh/guide/vue) —— `<Turing>` 元件
- [React](/zh/guide/react) —— `<Turing />` 元件

要移植到別的語言?[wire contract](/zh/reference/wire-contract) 是權威規格,
綁定凍結的測試 vectors。
