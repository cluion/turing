# React

`@cluion/turing-react` 把 `<turing-captcha>` Web Component 封裝成帶 callback props
的 React 元件。相容 React 18 與 19。

## 安裝

```bash
pnpm add @cluion/turing-react
```

`react` 是 peer dependency(`^18 || ^19`),永遠不會被打包進去。

## 使用

```tsx
import { Turing } from '@cluion/turing-react';

export function SignupForm() {
  return (
    <form method="post" action="/submit">
      <Turing
        url="/turing/challenge"
        type="pow"
        onSolved={() => {/* 啟用送出等等 */}}
        onError={(error) => console.error(error)}
      />
      <button type="submit">送出</button>
    </form>
  );
}
```

## Props

| Prop       | 必填 | 說明 |
|------------|------|------|
| `url`      | 是   | Challenge 端點 URL。 |
| `type`     | 否   | Challenge 類型,例如 `pow`。 |
| `field`    | 否   | 隱藏 input 的名稱(預設 `turing_token`)。 |
| `onSolved` | 否   | token 注入後呼叫。 |
| `onError`  | 否   | 失敗時帶著錯誤呼叫。 |

元件渲染在 **light DOM**,所以被注入的隱藏 input 會留在你的 `<form>` 裡、正常送出。
事件透過 ref effect 接線(含清理),所以 callback 在重新渲染間都保持最新。
