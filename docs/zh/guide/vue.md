# Vue

`@cluion/turing-vue` 把 `<turing-captcha>` Web Component 封裝成慣用的 Vue 元件,
帶有型別化的 props 與 emits。

## 安裝

```bash
pnpm add @cluion/turing-vue
```

`vue` 是 peer dependency(`^3.3`),永遠不會被打包進去。

## 使用

```vue
<script setup lang="ts">
import Turing from '@cluion/turing-vue';

function onSolved() {
  // 啟用送出按鈕等等。
}
</script>

<template>
  <form method="post" action="/submit">
    <Turing url="/turing/challenge" type="pow" @solved="onSolved" @error="onError" />
    <button type="submit">送出</button>
  </form>
</template>
```

## Props 與事件

| Prop    | 必填 | 說明 |
|---------|------|------|
| `url`   | 是   | Challenge 端點 URL。 |
| `type`  | 否   | Challenge 類型,例如 `pow`。 |
| `field` | 否   | 隱藏 input 的名稱(預設 `turing_token`)。 |

| Emit     | 何時觸發 | 內容 |
|----------|----------|------|
| `solved` | token 已注入表單。 | —— |
| `error`  | 抓取/解題/驗證失敗。 | 錯誤物件 |

元件渲染在 **light DOM**,所以被注入的隱藏 input 會留在你的 `<form>` 裡、正常送出。
所有行為都來自底層的 [Web Component](/zh/reference/attributes);這層封裝只負責把
props 與事件對應過去。
