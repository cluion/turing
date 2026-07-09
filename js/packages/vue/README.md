# @cluion/turing-vue

`<Turing>` Vue 3 component for the Turing captcha — a thin wrapper over the
[`<turing-captcha>`](https://www.npmjs.com/package/@cluion/turing-element) Web
Component with typed props and emits. `vue` is a peer dependency, never bundled.

## Install

```bash
pnpm add @cluion/turing-vue
```

## Use

```vue
<script setup lang="ts">
import Turing from '@cluion/turing-vue';
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

The component renders in **light DOM** so the injected hidden `turing_token`
input stays inside your `<form>` and submits normally. All behaviour lives in
the underlying element; this wrapper only translates props and events.
