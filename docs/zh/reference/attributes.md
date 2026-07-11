# 屬性與 props

headless 核心讀取的容器屬性，以及各框架封裝暴露的 props／事件，一份說清楚。它們最終
都對應到同一套底層行為。

## `[data-turing]` 容器（核心）

由 `@cluion/turing-core` 在掛載時讀取。Laravel 的 `<x-turing>` 與 Web Component 會幫你輸出這些。

| 屬性 | 必填 | 說明 |
|------|------|------|
| `data-turing` | 是 | 標記掛載容器。 |
| `data-turing-url` | 是 | Challenge 端點 URL。 |
| `data-turing-type` | 否 | 例如 `pow`。省略則用伺服器預設。 |
| `data-turing-field` | 否 | 隱藏 input 名稱。預設 `turing_token`。 |
| `data-turing-autostart` | 否 | 僅 PoW：進頁即解（不要勾選框）。預設為互動 UI。 |
| `data-turing-no-worker` | 否 | 僅 PoW：強制主執行緒。**Worker 預設開啟。** |

`data-turing-state`：`loading` / `idle` / `solving` / `solved` / `ready` / `error`。

## 事件

| 事件 | 何時 |
|------|------|
| `turing:solved` | token 已注入表單 |
| `turing:error` | 抓取／解題失敗 |
| `turing:ready` | 圖形題已畫出（math/text） |

## Laravel

```blade
<x-turing type="pow" />
<x-turing type="pow" autostart />
<x-turing type="pow" :no-worker="true" />
```
