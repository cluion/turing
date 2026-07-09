# @cluion/turing-react

`<Turing/>` React component for the Turing captcha — a thin wrapper over the
[`<turing-captcha>`](https://www.npmjs.com/package/@cluion/turing-element) Web
Component with callback props. Portable across React 18 and 19; `react` is a
peer dependency, never bundled.

## Install

```bash
pnpm add @cluion/turing-react
```

## Use

```tsx
import { Turing } from '@cluion/turing-react';

export function SignupForm() {
  return (
    <form method="post" action="/submit">
      <Turing
        url="/turing/challenge"
        type="pow"
        onSolved={() => {/* enable submit, etc. */}}
        onError={(error) => console.error(error)}
      />
      <button type="submit">Send</button>
    </form>
  );
}
```

## Props

| Prop       | Required | Description |
|------------|----------|-------------|
| `url`      | yes      | Challenge endpoint URL. |
| `type`     | no       | Challenge type, e.g. `pow`. |
| `field`    | no       | Hidden input name (default `turing_token`). |
| `onSolved` | no       | Called once the token is injected. |
| `onError`  | no       | Called with the error on failure. |

The component renders in **light DOM** so the injected hidden `turing_token`
input stays inside your `<form>` and submits normally. Events are wired via a
ref effect (with cleanup), so callbacks stay current across re-renders.
