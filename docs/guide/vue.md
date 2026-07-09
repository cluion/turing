# Vue

`@cluion/turing-vue` wraps the `<turing-captcha>` Web Component as an idiomatic
Vue component with typed props and emits.

## Install

```bash
pnpm add @cluion/turing-vue
```

`vue` is a peer dependency (`^3.3`); it is never bundled.

## Use

```vue
<script setup lang="ts">
import Turing from '@cluion/turing-vue';

function onSolved() {
  // enable the submit button, etc.
}
</script>

<template>
  <form method="post" action="/submit">
    <Turing url="/turing/challenge" type="pow" @solved="onSolved" @error="onError" />
    <button type="submit">Send</button>
  </form>
</template>
```

## Props & events

| Prop    | Required | Description |
|---------|----------|-------------|
| `url`   | yes      | Challenge endpoint URL. |
| `type`  | no       | Challenge type, e.g. `pow`. |
| `field` | no       | Hidden input name (default `turing_token`). |

| Emit     | When | Payload |
|----------|------|---------|
| `solved` | Token injected into the form. | — |
| `error`  | Fetch/solve/validation failed. | the error |

The component renders in **light DOM** so the injected hidden input stays inside
your `<form>` and submits normally. All behaviour comes from the underlying
[Web Component](/reference/attributes); this wrapper only translates props and
events.
