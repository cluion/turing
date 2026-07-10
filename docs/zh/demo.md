---
head:
  - - script
    - { src: ../turing.global.js, defer: '' }
---

# 線上示範

這一頁載入真正的 `turing.global.js` widget,並把它指向一個預先產生的靜態 PoW
challenge(`../mock-challenge.json`),所以整個解題過程**完全在瀏覽器端、不需後端**
——這正是真實頁面會做的事,只少了伺服器發 challenge 那一步。

<ClientOnly>
<form method="post" action="#" style="display:grid;gap:1rem;max-width:32rem">
  <div
    data-turing
    data-turing-url="../mock-challenge.json"
    data-turing-type="pow"></div>
  <button type="button">送出(token 已注入上方)</button>
</form>
</ClientOnly>

打開開發者工具,看著 widget 掛載、用瀏覽器原生 Web Crypto 解開 proof-of-work、
再把一個隱藏的 `turing_token` input 注入表單。在真實網站上,這個 token 會被送出並
在伺服器端驗證(見 [Laravel 指南](/zh/guide/laravel))。

::: tip 同一個 widget、真正的加密
這裡沒有假的 solver——頁面載入的是那個已發布的 bundle 並真的把 challenge 解出來。
只有 challenge JSON 是靜態的。
:::

## 部署文件

`docs:build` 會產生一個完全靜態的站台在 `docs/.vitepress/dist`。把那個目錄放到任何
靜態主機(Netlify、Pages、S3、nginx)即可,不需要任何 runtime。CI job 可以在 push
時建置並發布;widget bundle 會從 core 套件的建置同步進 `public/`。
