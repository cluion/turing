# 屬性與 props

headless 核心讀取的容器屬性，以及各框架封裝暴露的 props／事件。

## 行為屬性

| 屬性 | 必填 | 說明 |
|------|------|------|
| `data-turing` | 是 | 標記掛載容器 |
| `data-turing-url` | 是 | Challenge 端點 |
| `data-turing-type` | 否 | 例如 `pow` |
| `data-turing-field` | 否 | 預設 `turing_token` |
| `data-turing-autostart` | 否 | 進頁即算，不要勾選框 |
| `data-turing-no-worker` | 否 | 強制主執行緒（Worker 預設開） |

## 文案（PoW labels）

| 屬性 | 預設 | 何時 |
|------|------|------|
| `data-turing-label` | — | **idle 簡寫**（勾選框那行） |
| `data-turing-label-idle` | `I'm not a robot` | 等待勾選（優先於簡寫） |
| `data-turing-label-loading` | `Loading…` | 抓題中 |
| `data-turing-label-solving` | `Verifying…` | 計算中 |
| `data-turing-label-solved` | `Verified` | 成功 |
| `data-turing-label-error` | `Verification failed — try again` | 失敗 |
| `data-turing-label-aria` | `Verify you are human` | checkbox aria-label |

## Laravel

```blade
<x-turing
  type="pow"
  label="我不是機器人"
  label-solving="驗證中…"
  label-solved="已通過"
  label-error="驗證失敗，請再試一次"
/>
```

## Vue / React

```vue
<Turing url="..." type="pow" label="我不是機器人" label-solving="驗證中…" />
```

```jsx
<Turing url="..." type="pow" label="我不是機器人" labelSolving="驗證中…" />
```
