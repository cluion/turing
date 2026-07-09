# Attributes & props

One source of truth for the container attributes the headless core reads and the
props/events the framework wrappers expose. They all map onto the same
underlying behaviour.

## `[data-turing]` container (core)

Read by `@cluion/turing-core` on mount. The Laravel `<x-turing>` component and
the Web Component emit these for you.

| Attribute            | Required | Description |
|----------------------|----------|-------------|
| `data-turing`        | yes      | Marks the mount container. |
| `data-turing-url`    | yes      | Challenge endpoint URL. |
| `data-turing-type`   | no       | Challenge type, e.g. `pow`. Omit for the server default. |
| `data-turing-field`  | no       | Hidden input name. Default `turing_token`. |
| `data-turing-worker` | no       | Presence opts the PoW solve into a Web Worker (falls back to inline when Worker is unavailable). |

State is reflected on `data-turing-state` (`solved` / `error`).

## `<turing-captcha>` element & `<Turing>` wrappers

The [Web Component](/guide/plain-html) and the [Vue](/guide/vue) /
[React](/guide/react) wrappers take the same three inputs:

| Prop / attribute | Required | Maps to |
|------------------|----------|---------|
| `url`            | yes      | `data-turing-url` |
| `type`           | no       | `data-turing-type` |
| `field`          | no       | `data-turing-field` |

Outcomes surface as events (Web Component / Vue) or callback props (React):

| Web Component / Vue | React      | When |
|---------------------|------------|------|
| `turing:solved`     | `onSolved` | Token injected into the form. |
| `turing:error`      | `onError`  | Fetch/solve/validation failed (carries the error). |

All wrappers render in **light DOM** so the injected hidden input stays inside
the enclosing `<form>` and submits normally.
