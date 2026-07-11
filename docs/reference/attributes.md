# Attributes & props

One source of truth for the container attributes the headless core reads and the
props/events the framework wrappers expose. They all map onto the same
underlying behaviour.

## `[data-turing]` container (core)

Read by `@cluion/turing-core` on mount. The Laravel `<x-turing>` component and
the Web Component emit these for you.

| Attribute                | Required | Description |
|--------------------------|----------|-------------|
| `data-turing`            | yes      | Marks the mount container. |
| `data-turing-url`        | yes      | Challenge endpoint URL. |
| `data-turing-type`       | no       | Challenge type, e.g. `pow`. Omit for the server default. |
| `data-turing-field`      | no       | Hidden input name. Default `turing_token`. |
| `data-turing-autostart`  | no       | PoW only: solve immediately (no checkbox). Default is interactive UI. |
| `data-turing-no-worker`  | no       | PoW only: force main-thread solve. **Workers are on by default.** |

### Labels (PoW copy)

| Attribute | Default | When shown |
|-----------|---------|------------|
| `data-turing-label` | — | Shorthand for **idle** only. |
| `data-turing-label-idle` | `I'm not a robot` | Checkbox waiting (wins over shorthand). |
| `data-turing-label-loading` | `Loading…` | Fetching the challenge. |
| `data-turing-label-solving` | `Verifying…` | PoW running. |
| `data-turing-label-solved` | `Verified` | Success. |
| `data-turing-label-error` | `Verification failed — try again` | Failure. |
| `data-turing-label-aria` | `Verify you are human` | Checkbox `aria-label`. |
| `data-turing-label-refresh` | `Refresh` | Math/text refresh button. |
| `data-turing-no-refresh` | — | Hide the math/text refresh control. |

State is reflected on `data-turing-state`:

| State | Meaning |
|-------|---------|
| `loading` | Fetching the challenge. |
| `idle` | PoW checkbox shown; waiting for the user. |
| `solving` | PoW in progress. |
| `solved` | Token injected (PoW done, or image answer typed). |
| `ready` | Image challenge ready for user input. |
| `error` | Fetch/solve failed. |

## `<turing-captcha>` element & `<Turing>` wrappers

| Prop / attribute | Required | Maps to |
|------------------|----------|---------|
| `url`            | yes      | `data-turing-url` |
| `type`           | no       | `data-turing-type` |
| `field`          | no       | `data-turing-field` |
| `autostart`      | no       | `data-turing-autostart` |
| `no-worker` / `noWorker` | no | `data-turing-no-worker` |
| `label`          | no       | `data-turing-label` (idle shorthand) |
| `label-idle` / `labelIdle` | no | `data-turing-label-idle` |
| `label-loading` / `labelLoading` | no | `data-turing-label-loading` |
| `label-solving` / `labelSolving` | no | `data-turing-label-solving` |
| `label-solved` / `labelSolved` | no | `data-turing-label-solved` |
| `label-error` / `labelError` | no | `data-turing-label-error` |
| `label-aria` / `labelAria` | no | `data-turing-label-aria` |

Outcomes surface as events (Web Component / Vue) or callback props (React):

| Web Component / Vue | React      | When |
|---------------------|------------|------|
| `turing:solved`     | `onSolved` | Token injected into the form. |
| `turing:error`      | `onError`  | Fetch/solve/validation failed (carries the error). |
| `turing:ready`      | `onReady`  | Image challenge painted (math/text). |

All wrappers render in **light DOM** so the injected hidden input stays inside
the enclosing `<form>` and submits normally.

## Laravel `<x-turing>`

```blade
{{-- English defaults --}}
<x-turing type="pow" />

{{-- Chinese copy --}}
<x-turing
  type="pow"
  label="我不是機器人"
  label-solving="驗證中…"
  label-solved="已通過"
  label-error="驗證失敗，請再試一次"
/>

{{-- Auto-solve, no checkbox --}}
<x-turing type="pow" autostart />
```

| Prop | Maps to |
|------|---------|
| `type` / `field` / `url` | same as above |
| `autostart` | `data-turing-autostart` |
| `noWorker` (`no-worker`) | `data-turing-no-worker` |
| `label` | `data-turing-label` |
| `labelIdle` / `labelLoading` / `labelSolving` / `labelSolved` / `labelError` / `labelAria` | matching `data-turing-label-*` |

## Vue / React

```vue
<Turing
  url="/turing/challenge"
  type="pow"
  label="我不是機器人"
  label-solving="驗證中…"
  label-solved="已通過"
  @solved="onSolved"
/>
```

```jsx
<Turing
  url="/turing/challenge"
  type="pow"
  label="我不是機器人"
  labelSolving="驗證中…"
  labelSolved="已通過"
  onSolved={onSolved}
/>
```
