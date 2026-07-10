# Wire contract（線上協定）

這是任何語言移植版都必須重現的權威規格。下面每個區塊都**逐字**引用
`php/tests/vectors/` 裡對應的 fixture——docs 建置步驟(`scripts/check-vectors.mjs`)
會在這裡的 JSON 與真實 vector 檔案不一致時失敗,所以移植者可以直接拿這些精確的
位元組來 diff、並且信任它們。

## Token 結構

一個 token 是一個 compact 的兩段字串:

```
base64url(canonicalJson(payload)) . base64url(signature)
```

- **base64url** 是 RFC 4648 §5 無 padding(`+`→`-`、`/`→`_`、無 `=`)。
- 簽章涵蓋的是**第一段的位元組**(base64url 後的 payload 字串),不是原始 JSON。
- payload 帶有 `v`(版本)、`type`、`kid`(key id)、`nonce`、`iat`、`exp`,以及一個
  `data` 物件。

## Canonical JSON

payload 以決定性方式序列化,好讓每種語言都產生相同的位元組:

- 物件的 key **遞迴排序**,依位元組值遞增。
- 斜線與 Unicode **不跳脫**(PHP `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`)。
- 沒有多餘空白。
- 整數樣式的 key 必須保持字串語意——JSON 編碼器若重排或強制轉換數字 key 就不會相符;
  遇到這種 payload 應該拒絕,而不是默默重排。

## 簽章

預設是 HMAC-SHA256(HS256);Ed25519(EdDSA,detached)為選用。

### HMAC-SHA256（預設）

<!-- vector:hmac-roundtrip.json -->
```json
{
  "description": "HMAC-SHA256 token round-trip. A port must produce this identical compact token from the same secret and payload (canonical JSON, sorted keys).",
  "secret": "vector-secret",
  "payload": {
    "v": 1,
    "type": "math",
    "kid": "k1",
    "nonce": "vgvectorsnonce000",
    "iat": 1000,
    "exp": 1120,
    "data": { "ah": "demo" }
  },
  "expectedCompact": "eyJkYXRhIjp7ImFoIjoiZGVtbyJ9LCJleHAiOjExMjAsImlhdCI6MTAwMCwia2lkIjoiazEiLCJub25jZSI6InZndmVjdG9yc25vbmNlMDAwIiwidHlwZSI6Im1hdGgiLCJ2IjoxfQ.hrkMQ2BPWAI3Z8fJp8XbsSLUtWfoVd0jI5hJDXTXJgo"
}
```

### Ed25519（選用,`ext-sodium`）

決定性簽章,所以 compact token 是穩定的。對 canonical JSON payload 做 detached 簽章。

<!-- vector:ed25519-roundtrip.json -->
```json
{
  "description": "Ed25519 token round-trip (opt-in, ext-sodium). Keypair derived from a fixed 32-byte seed (all 0x01). Ed25519 signatures are deterministic, so the compact token is stable. Detached signature over the canonical JSON payload.",
  "enabled": true,
  "seedHex": "0101010101010101010101010101010101010101010101010101010101010101",
  "publicKeyHex": "8a88e3dd7409f195fd52db2d3cba5d72ca6709bf1d94121bf3748801b40f6f5c",
  "payload": {
    "v": 1,
    "type": "pow",
    "kid": "ed",
    "nonce": "n",
    "iat": 1,
    "exp": 2,
    "data": { "ah": "demo" }
  },
  "expectedCompact": "eyJkYXRhIjp7ImFoIjoiZGVtbyJ9LCJleHAiOjIsImlhdCI6MSwia2lkIjoiZWQiLCJub25jZSI6Im4iLCJ0eXBlIjoicG93IiwidiI6MX0.JAFWow6q2xYjk1t0iiBRG5_UXj7mXCm_SA-OMgvKlqGYhd2MWyqifkz0-e_GJF8X_xO1ht40SF7uvlFCeR9SDQ"
}
```

## Proof-of-work

預設是 PBKDF2-SHA256 Deterministic(伺服器植入一個 `keySignature`,client 暴力
搜尋 counter);SHA-256 leading-zero-bit 為選用。所有二進位 payload 欄位都是原始
位元組的 base64url。

### PBKDF2-SHA256（預設）

移植版計算 `PBKDF2(nonce + counter, salt, cost)` 並做 base64url 編碼;結果必須等於
`keySignature`。相符的最小 counter 就是答案。

<!-- vector:pow-pbkdf2.json -->
```json
{
  "description": "PBKDF2-SHA256 Deterministic PoW. A port derives PBKDF2(nonce+counter, salt, cost) and base64url-encodes it; the result must equal keySignature. keySignature is base64url of the raw derived key.",
  "challenge": {
    "algorithm": "PBKDF2-SHA256",
    "salt": "vsalt",
    "nonce": "vnonce",
    "cost": 100,
    "keySignature": "5BkpOKxye8efvkkqtqzTXTKULjSJdxLtfyJW2Q29w0E"
  },
  "correctCounter": 7
}
```

### SHA-256 leading-zero-bit（選用）

數 `SHA-256(salt . counter)` 的前導零 **bit** 數;答案是第一個 digest 至少有
`difficulty_bits` 個前導零的最小 counter。

<!-- vector:pow-shabit.json -->
```json
{
  "description": "SHA-256 leading-zero-bit (hashcash-style) PoW. A port must reproduce identical leading-zero-bit counting of SHA-256(salt . counter).",
  "challenge": { "algorithm": "SHA-256", "salt": "vsalt", "difficulty_bits": 4 },
  "correctCounter": 0
}
```

## 一次性

預設無狀態;`Store` 讓 nonce 只能用一次(發行時記住、驗證時消費一次),讓已解開的
token 無法被重放。
