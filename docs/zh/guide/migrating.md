# 從傳統 Laravel captcha 遷移

若你已在 Blade 顯示圖形／數學挑戰，並用驗證規則檢查答案，Turing 對應同一套習慣：
**一行顯示、一行驗證**。防 bot 請優先 **PoW**；math/text 為方便級。

Turing **沒有** session 存答案模式。挑戰是簽章 token；Laravel 預設用 cache 做
nonce 單次使用。

## 對照

| 傳統做法 | Turing |
|----------|--------|
| 僅 Laravel 的 captcha 套件 | `composer require cluion/turing` |
| 發布套件設定 | `vendor:publish --tag=turing-config` + `TURING_SECRET` |
| helper 輸出圖／URL | `<x-turing type="pow" />`（或 `math` / `text`） |
| 獨立答案 input | widget 注入 `turing_token`（math/text 另有可見輸入） |
| 答案欄位的 validation rule | `'turing_token' => 'required\|turing'` |
| Session 存正確答案 | 簽章 token；`store=cache` 單次 nonce |
| 頁面與 API 兩套規則 | 所有 type 同一欄位、同一 rule |
| 伺服器端繪圖（GD 等） | SVG path（math/text）或無圖（pow） |

## 最小替換（建議 PoW）

```js
import '@cluion/turing-core';
```

```blade
<form method="post" action="/register">
  @csrf
  <x-turing type="pow" />
  <button type="submit">註冊</button>
</form>
```

```php
$request->validate([
    'turing_token' => 'required|turing',
]);
```

預設勾選後才算力；進頁即算：

```blade
<x-turing type="pow" autostart />
```

較接近「看圖打字」：

```blade
<x-turing type="math" />
```

## 設定

```dotenv
TURING_SECRET=長隨機字串
# TURING_POW_PROFILE=balanced   # interactive | balanced | strict
```

## 可拿掉的

- 只為 captcha 裝的繪圖擴充  
- 專用 captcha session  
- 多套 mode 的欄位與 rule  
- 為基本表單呼叫外部 captcha 服務  

## 誠實差異

| 主題 | 說明 |
|------|------|
| Session 答案 | 尚未提供；多數場景 cache 單次 nonce 即可 |
| 外觀 | PoW 是勾選 + 狀態；文案可用 label props |
| 強度 | 真防濫用靠 **pow** |
| 前端 | 需要 widget（或自實作 wire contract） |

更多：[Laravel 指南](/zh/guide/laravel) · [屬性](/zh/reference/attributes) · [Wire contract](/zh/reference/wire-contract)
