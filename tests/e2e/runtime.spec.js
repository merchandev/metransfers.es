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

test('booking search uses a readable vertical layout in a narrow hero panel', async ({ page }) => {
  await page.setViewportSize({ width: 800, height: 900 });
  await page.goto('/tests/e2e/fixtures/booking-search.html');

  const fields = page.locator('.wptb-main-search-field');
  const boxes = await fields.evaluateAll((elements) => elements.map((element) => {
    const box = element.getBoundingClientRect();
    const label = element.querySelector('label').getBoundingClientRect();
    const input = element.querySelector('input').getBoundingClientRect();
    return { x: box.x, y: box.y, height: input.height, labelBottom: label.bottom, inputTop: input.top };
  }));

  expect(boxes).toHaveLength(4);
  expect(new Set(boxes.map((box) => Math.round(box.x))).size).toBe(1);
  expect(boxes.every((box) => box.height >= 58)).toBe(true);
  expect(boxes.every((box) => box.labelBottom <= box.inputTop)).toBe(true);
  const submitBox = await page.getByRole('button', { name: 'Buscar vehículos' }).boundingBox();
  expect(submitBox.width).toBeGreaterThan(380);
});

test('booking search only creates columns when its own container is wide enough', async ({ page }) => {
  await page.setViewportSize({ width: 1100, height: 800 });
  await page.goto('/tests/e2e/fixtures/booking-search.html');
  await page.locator('.fixture-panel').evaluate((element) => element.classList.add('is-wide'));

  const positions = await page.locator('.wptb-main-search-field').evaluateAll((elements) => elements.map((element) => {
    const box = element.getBoundingClientRect();
    return { x: Math.round(box.x), y: Math.round(box.y) };
  }));

  expect(positions[0].y).toBe(positions[1].y);
  expect(positions[0].x).not.toBe(positions[1].x);
  expect(positions[2].y).toBe(positions[3].y);
  expect(positions[2].y).toBeGreaterThan(positions[0].y);
});

test('hotel portal mobile drawer exposes its state accessibly', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/tests/e2e/fixtures/hotel-portal.html');

  const trigger = page.getByRole('button', { name: 'Abrir menú' });
  const sidebar = page.getByRole('complementary', { name: 'Navegación del Portal de Hoteles' });
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await trigger.click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(sidebar).toHaveClass(/is-open/);
  await page.keyboard.press('Escape');
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(trigger).toBeFocused();

  await trigger.click();
  await page.getByRole('button', { name: 'Cerrar menú' }).click();
  await expect(sidebar).not.toHaveClass(/is-open/);
  await expect(page.locator('body')).not.toHaveClass(/has-open-drawer/);
});

test('hotel portal keeps touch controls at least 44 pixels high', async ({ page }) => {
  await page.setViewportSize({ width: 768, height: 900 });
  await page.goto('/tests/e2e/fixtures/hotel-portal.html');

  const menuBox = await page.getByRole('button', { name: 'Abrir menú' }).boundingBox();
  const hotelBox = await page.getByLabel('Hotel actual').boundingBox();
  const passwordBox = await page.getByRole('button', { name: 'Mostrar' }).boundingBox();
  const helpBox = await page.getByRole('link', { name: '¿Has olvidado tu contraseña?' }).boundingBox();
  expect(menuBox.height).toBeGreaterThanOrEqual(44);
  expect(hotelBox.height).toBeGreaterThanOrEqual(44);
  expect(passwordBox.height).toBeGreaterThanOrEqual(44);
  expect(passwordBox.width).toBeGreaterThanOrEqual(44);
  expect(helpBox.height).toBeGreaterThanOrEqual(44);
});

test('hotel portal password labels use localized values', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/hotel-portal.html');
  const passwordToggle = page.locator('.mt-hotel-password-toggle');
  await passwordToggle.click();
  await expect(passwordToggle).toHaveText('Esconder');
  await passwordToggle.click();
  await expect(passwordToggle).toHaveText('Ver');
});
