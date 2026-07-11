# 安全

Turing 是自架方案：由你的伺服器發題與驗證，不呼叫第三方 captcha 服務。

## 類型

| 類型 | 是什麼 | 適用 |
|------|--------|------|
| `pow` | 瀏覽器先付出 CPU（PBKDF2 或 SHA-bit）再讓表單通過 | 公開或流量高的表單（預設） |
| `math` / `text` | 使用者解一小題視覺挑戰 | 只要輕度摩擦；不宜當唯一防線 |

PoW 提高大量自動化的成本，不能識別「是不是真人」。math／text 避免 F12 直接抄
字，但認真寫的腳本仍可能過。表單值得被打時優先 `pow`。

PoW 難度檔位：`interactive`、`balanced`、`strict`（見 Laravel 指南或
`TURING_POW_PROFILE`）。

## 伺服器端已有的

| 項目 | Laravel 預設 |
|------|----------------|
| Token 有效期 | 約 120 秒（`exp`） |
| 單次 nonce | `store=cache` |
| 發題限流 | `throttle:60,1` |
| 簽章密鑰 | `TURING_SECRET`（必填） |

Core 可不使用 store。Laravel 預設用 cache，避免解過的 token 重送。

## 應用層仍要做

- 登入／註冊等敏感 POST 的限流  
- HTTPS，以及夠長、不進版控的 `TURING_SECRET`  
- 你自己的業務風險規則  

驗證失敗可寫 log code（如 `expired`、`already_used`、`wrong_answer`）供統計，
不要把 code 顯示給使用者。

## CSP

Blade 掛載點不輸出 inline script。預設 PoW 只用 Web Crypto（無 WASM）。客戶端
腳本請同源，或 CDN 固定版本並加 SRI。
