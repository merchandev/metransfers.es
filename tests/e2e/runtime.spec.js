import { expect, test } from '@playwright/test';

test('language switcher is keyboard accessible', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/i18n.html');
  const trigger = page.getByRole('button', { name: 'Cambiar idioma' });
  const menu = page.getByRole('navigation', { name: 'Selector de idioma' });

  await trigger.focus();
  await page.keyboard.press('Enter');
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(menu).toHaveClass(/open/);
  await expect(page.getByRole('button', { name: 'Cerrar selector de idioma' })).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(trigger).toBeFocused();
});

test('mobile language switcher locks and restores document scroll', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/tests/e2e/fixtures/i18n.html');

  await page.getByRole('button', { name: 'Cambiar idioma' }).click();
  await expect(page.locator('body')).toHaveClass(/mt-lang-open/);
  await page.getByRole('button', { name: 'Cerrar selector de idioma' }).click();
  await expect(page.locator('body')).not.toHaveClass(/mt-lang-open/);
});

test('purchase event requires a server-confirmed booking and is idempotent', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/tracking-confirmed.html');
  await expect.poll(() => page.evaluate(() => window.dataLayer || [])).toHaveLength(1);

  const purchase = await page.evaluate(() => window.dataLayer[0]);
  expect(purchase).toEqual({
    event: 'purchase',
    ecommerce: {
      transaction_id: 'MT-9001',
      value: 123.45,
      currency: 'EUR'
    }
  });

  await page.reload();
  await expect.poll(() => page.evaluate(() => window.dataLayer || [])).toHaveLength(0);
});

test('pending booking cannot emit a purchase event', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/tracking-unconfirmed.html');
  await page.waitForLoadState('networkidle');

  expect(await page.evaluate(() => window.dataLayer || [])).toEqual([]);
});

test('confirmed receipt clears legacy booking session data', async ({ page }) => {
  await page.addInitScript(() => {
    window.sessionStorage.setItem('wptb_booking_data', JSON.stringify({ customer_email: 'private@example.test' }));
  });
  await page.goto('/tests/e2e/fixtures/confirmation.html');

  await expect.poll(() => page.evaluate(() => window.sessionStorage.getItem('wptb_booking_data'))).toBeNull();
});

test('contact tracking accepts allowlisted events only', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/contact.html');
  await page.locator('a').evaluateAll((links) => {
    links.forEach((link) => link.addEventListener('click', (event) => event.preventDefault()));
  });
  await page.evaluate(() => window.mtBookingTrack('not_allowed', { private: 'value' }));
  await page.getByRole('link', { name: 'Phone' }).click();
  await page.getByRole('link', { name: 'WhatsApp' }).click();

  const events = await page.evaluate(() => (window.dataLayer || []).map((entry) => entry.event));
  expect(events).not.toContain('not_allowed');
  expect(events).toContain('click_phone');
  expect(events).toContain('click_whatsapp');
});
