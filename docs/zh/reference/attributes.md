# 屬性與 props

headless 核心讀取的容器屬性,以及各框架封裝暴露的 props/事件,一份說清楚。它們最終
都對應到同一套底層行為。

## `[data-turing]` 容器(核心)

由 `@cluion/turing-core` 在掛載時讀取。Laravel 的 `<x-turing>` 元件與 Web Component
會幫你輸出這些。

| 屬性                 | 必填 | 說明 |
|----------------------|------|------|
| `data-turing`        | 是   | 標記掛載容器。 |
| `data-turing-url`    | 是   | Challenge 端點 URL。 |
| `data-turing-type`   | 否   | Challenge 類型,例如 `pow`。省略則用伺服器預設。 |
| `data-turing-field`  | 否   | 隱藏 input 的名稱。預設 `turing_token`。 |
| `data-turing-worker` | 否   | 只要存在,就把 PoW 解題丟到 Web Worker(Worker 不可用時退回主執行緒 inline)。 |

狀態會反映在 `data-turing-state`(`solved` / `error`)。

## `<turing-captcha>` 元素與 `<Turing>` 封裝

[Web Component](/zh/guide/plain-html) 與 [Vue](/zh/guide/vue) /
[React](/zh/guide/react) 封裝接收相同的三個輸入:

| Prop / 屬性 | 必填 | 對應到 |
|-------------|------|--------|
| `url`       | 是   | `data-turing-url` |
| `type`      | 否   | `data-turing-type` |
| `field`     | 否   | `data-turing-field` |

結果以事件(Web Component / Vue)或 callback props(React)呈現:

| Web Component / Vue | React      | 何時 |
|---------------------|------------|------|
| `turing:solved`     | `onSolved` | token 已注入表單。 |
| `turing:error`      | `onError`  | 抓取/解題/驗證失敗(帶著錯誤)。 |

所有封裝都渲染在 **light DOM**,所以被注入的隱藏 input 會留在所在的 `<form>` 裡、
正常送出。
