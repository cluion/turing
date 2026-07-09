import { expect, test } from '@playwright/test';

test('solves the PoW and the server accepts the submission', async ({ page }) => {
  const posted = page.waitForResponse((r) => r.url().endsWith('/submit'));
  await page.goto('/captcha-demo');
  // widget mounts and solves: the hidden input gets a value
  await expect(page.locator('input[name="turing_token"]')).toHaveValue(/.+/, { timeout: 15_000 });
  await page.getByRole('button', { name: 'Submit' }).click();
  const res = await posted;
  expect(res.status()).toBe(200);
  expect(await res.json()).toEqual({ ok: true });
});

test('a tampered token is rejected', async ({ page }) => {
  await page.goto('/captcha-demo');
  await expect(page.locator('input[name="turing_token"]')).toHaveValue(/.+/, { timeout: 15_000 });
  await page.evaluate(() => {
    (document.querySelector('input[name="turing_token"]') as HTMLInputElement).value = 'tampered.token';
  });
  const posted = page.waitForResponse((r) => r.url().endsWith('/submit'));
  await page.getByRole('button', { name: 'Submit' }).click();
  // A native browser form post sends Accept: text/html, so Laravel rejects a
  // failed validation with a 302 redirect back to the form (JSON clients get
  // 422). The redirect back — instead of the ok payload — is the rejection.
  expect((await posted).status()).toBe(302);
  await page.waitForURL('**/captcha-demo');
});
