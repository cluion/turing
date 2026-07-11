import { expect, test } from '@playwright/test';

/** Click the interactive PoW checkbox and wait for the hidden token. */
async function solvePow(page: import('@playwright/test').Page) {
  await page.locator('[data-turing-check]').check();
  await expect(page.locator('input[name="turing_token"]')).toHaveValue(/.+/, { timeout: 15_000 });
  await expect(page.locator('[data-turing]')).toHaveAttribute('data-turing-state', 'solved');
}

test('solves the PoW and the server accepts the submission', async ({ page }) => {
  const posted = page.waitForResponse((r) => r.url().endsWith('/submit'));
  await page.goto('/captcha-demo');
  await solvePow(page);
  await page.getByRole('button', { name: 'Submit' }).click();
  const res = await posted;
  expect(res.status()).toBe(200);
  expect(await res.json()).toEqual({ ok: true });
});

test('a tampered token is rejected', async ({ page }) => {
  await page.goto('/captcha-demo');
  await solvePow(page);
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
