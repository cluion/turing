# Laravel

Turing 提供 Laravel 整合:一個 service provider、渲染掛載點的 `<x-turing>` Blade
元件、一個 challenge 端點,以及一個 `turing` 驗證規則。

## 安裝

```bash
composer require cluion/turing
```

發布設定並設定密鑰:

```bash
php artisan vendor:publish --tag=turing-config
```

```dotenv
# .env —— 必填;token 用它簽章。
TURING_SECRET=change-me-to-a-long-random-string

# 可選 PoW 難度：interactive | balanced | strict
# TURING_POW_PROFILE=balanced
```

service provider 會自動註冊具名的 challenge 路由(`turing.challenge`,位於
`/turing/challenge`)、`turing` 驗證器,以及 `<x-turing>` 元件。

## PoW 難度 profiles

用命名檔位，不必手調 `cost` / `maxcounter`。在 `config/turing.php` 的 `types.pow` 或 env `TURING_POW_PROFILE`：

| Profile | cost | maxcounter | 適用 |
|---------|------|------------|------|
| `interactive` | 1000 | 2500 | 一般表單、要快 |
| `balanced` | 5000 | 10000 | 預設 |
| `strict` | 15000 | 25000 | 註冊／敏感操作 |

明確設定的 `cost` / `maxcounter` 會蓋過 profile。未知名稱退回 `balanced`。

## 渲染 widget

把元件放進 `<form>` 裡,再加上前端 widget(`<script>` 見[純 HTML](/zh/guide/plain-html)):

```blade
<form method="post" action="/submit">
  @csrf
  <x-turing type="pow" />
  <button type="submit">送出</button>
</form>
```

`<x-turing>` 渲染一個 CSP 安全的容器——一個帶著已解析 challenge URL 的
`data-turing` `<div>`。這裡不會輸出任何 inline script。前端 widget 掛載其上、解開
challenge、注入一個隱藏的 `turing_token` input。

要換掉舊的圖形／session captcha？見[遷移指南](/zh/guide/migrating)。

## 支援的 Laravel 版本

Laravel **10–13**（`illuminate/support` `^10 || ^11 || ^12 || ^13`）。CI 對各大版
跑 PHPUnit。

## 驗證送出

用 `turing` 規則驗證 `turing_token`:

```php
Route::post('/submit', function (Illuminate\Http\Request $request) {
    $request->validate(['turing_token' => 'required|turing']);

    return response()->json(['ok' => true]);
});
```

這個規則會安全地失敗——答錯或被竄改的 token 只會回傳驗證錯誤,不會拋出例外。

## 一次性 nonce

預設的 store 是 cache(`Cache::pull`,取用即刪),讓每個 nonce 只能用一次,
已解開的 token 無法被重放。把 `turing.store` 指向 app 已在用的任何 cache 即可。

完整可跑的範例在 [`workbench/`](https://github.com/cluion/turing)
(`vendor/bin/testbench serve`,然後開 `/captcha-demo`)。
