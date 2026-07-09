<!doctype html>
<meta charset="utf-8">
<title>Turing demo</title>
{{-- Built widget; served from the core package dist by the workbench route. --}}
<script src="/turing.global.js" defer></script>

<form method="post" action="/submit">
  @csrf
  <x-turing type="pow" />
  <button type="submit">Submit</button>
</form>
