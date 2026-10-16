import { expect, test } from '@playwright/test';

for (const lang of ['es', 'en']) {
  test(`airport quote keeps the lead payload and online booking link (${lang})`, async ({ page }) => {
    let payload;
    await page.route('**/wp-admin/admin-ajax.php', async route => {
      payload = route.request().postData();
      await route.fulfill({ json: { success: true, data: { message: 'Backend receipt' } } });
    });
    await page.goto(`/tests/e2e/service-fixture?lang=${lang}`);
    await expect(page.locator('.svc-form')).toHaveAttribute('data-service', 'aeropuerto');
    await expect(page.locator('.svc-hero-cta-group a[href="#solicitar"]')).toHaveText(lang === 'en' ? 'Request a quote' : 'Solicitar presupuesto');
    await expect(page.locator(`.svc-hero-cta-group a[href="${lang === 'en' ? '/en/' : '/'}#panel"]`)).toBeVisible();
    await expect(page.locator(`.svc-hero-cta-group a[href="${lang === 'en' ? '/en/' : '/'}#panel"]`)).toHaveClass(/btn-primary/);
    await page.locator('[name="nombre"]').fill('Test passenger');
    await page.locator('[name="telefono"]').fill('+34000000000');
    await page.locator('[name="extra_fecha"]').fill('2026-10-04');
    await page.locator('[name="extra_hora"]').fill('12:00');
    await page.locator('[name="extra_vuelo"]').fill('TEST123');
    await page.locator('[name="extra_direccion"]').fill('BCN airport → H10 Casanova');
    await page.locator('[name="extra_pasajeros"]').selectOption('5');
    await page.locator('[name="gdpr_aceptado"]').check();
    await page.locator('.svc-submit-btn').click();
    await expect(page.locator('.svc-form-ok')).toHaveClass(/active/);
    await expect(page.locator('.svc-form-ok')).toHaveText(lang === 'en' ? 'Request received! We will get back to you shortly.' : '¡Solicitud recibida correctamente! Te responderemos muy pronto.');
    for (const fragment of ['mt_save_lead', 'isolated-fixture-nonce', 'formulario-aeropuerto', 'TEST123', '2026-10-04', 'H10 Casanova', 'gdpr_fecha']) {
      expect(payload).toContain(fragment);
    }
    await expect(page.locator('[name="nombre"]')).toHaveValue('');
    expect(await page.evaluate(() => window.dataLayer || [])).toEqual([]);
  });
}

test('airport quote failure preserves passenger details and allows a retry', async ({ page }) => {
  await page.route('**/wp-admin/admin-ajax.php', route => route.fulfill({ json: { success: false, data: { message: 'Please retry' } } }));
  await page.goto('/tests/e2e/service-fixture?lang=en');
  await page.locator('[name="nombre"]').fill('Test passenger');
  await page.locator('[name="telefono"]').fill('+34000000000');
  await page.locator('[name="extra_fecha"]').fill('2026-10-04');
  await page.locator('[name="gdpr_aceptado"]').check();
  await page.locator('.svc-submit-btn').click();
  await expect(page.locator('.svc-form-ok')).toHaveText('Please retry');
  await expect(page.locator('.svc-form-ok')).toHaveClass(/error/);
  await expect(page.locator('[name="nombre"]')).toHaveValue('Test passenger');
  await expect(page.locator('.svc-submit-btn')).toBeEnabled();
});

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

test('blue booking buttons and the payment summary title keep white text over the theme stylesheet', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/booking-contrast.html');

  const white = 'rgb(255, 255, 255)';
  for (const selector of ['#button-text', '#submit-payment', '.wptb-main-search-submit', '.trip-type-btn.active', '.select-vehicle-btn', 'a[href^="https://wa.me/"]', '.mt-checkout__summary h2']) {
    await expect(page.locator(selector)).toHaveCSS('color', white);
  }
  // Light buttons keep their dark text.
  await expect(page.locator('#wptb-payment-back')).not.toHaveCSS('color', white);
  await expect(page.locator('.trip-type-btn:not(.active)')).not.toHaveCSS('color', white);
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
  // The sidebar intentionally covers the left part of the overlay.
  const overlay = page.getByRole('button', { name: 'Cerrar menú' });
  const overlayBox = await overlay.boundingBox();
  await overlay.click({ position: { x: overlayBox.width - 12, y: 24 } });
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

function formField(body, name) {
  const match = body.match(new RegExp('name="' + name + '"\\r\\n\\r\\n([\\s\\S]*?)\\r\\n--'));
  return match ? match[1] : null;
}

// Stands in for admin-ajax: the popup's permission check (slot) and the signup.
async function mockNewsletter(page, { slot = true, status = 200, payload = { success: true, data: { message: 'OK' } } } = {}) {
  const calls = { slot: 0, body: '' };
  await page.context().route('**/newsletter-endpoint', async (route) => {
    const body = route.request().postData() || '';
    if (formField(body, 'action') === 'mt_newsletter_slot') {
      calls.slot += 1;
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, data: { show: slot } }) });
      return;
    }
    calls.body = body;
    await route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(payload) });
  });
  return calls;
}

test('newsletter popup opens after its delay, keeps focus inside and remembers a dismissal', async ({ page }) => {
  const calls = await mockNewsletter(page);
  await page.goto('/tests/e2e/fixtures/newsletter.html');
  const dialog = page.getByRole('dialog', { name: 'Ofertas y novedades de MeTransfers' });
  await expect(dialog).toHaveCount(0);
  await expect(dialog).toBeVisible({ timeout: 3000 });
  await expect(page.getByLabel('Nombre')).toBeFocused();

  await page.getByRole('button', { name: 'Cerrar' }).focus();
  await page.keyboard.press('Shift+Tab');
  await expect(page.getByRole('button', { name: 'Suscribirme' })).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(page.getByRole('button', { name: 'Cerrar' })).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(dialog).toHaveCount(0);
  const state = await page.evaluate(() => JSON.parse(window.localStorage.getItem('mt_newsletter_state')));
  expect(state.status).toBe('dismissed');
  expect(state.until).toBeGreaterThan(Date.now() + 13 * 86400000);

  // Even with a new session and the daily check expired, a dismissal is honoured without asking the server.
  await page.evaluate(() => {
    window.sessionStorage.removeItem('mt_newsletter_session');
    window.localStorage.removeItem('mt_newsletter_checked_at');
  });
  await page.reload();
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
  expect(calls.slot).toBe(1);
});

test('newsletter popup waits until the cookie banner is closed', async ({ page }) => {
  await mockNewsletter(page);
  await page.goto('/tests/e2e/fixtures/newsletter.html?banner=1');
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
  await page.evaluate(() => document.getElementById('mt-cookie-banner').classList.remove('show'));
  await expect(page.getByRole('dialog')).toBeVisible({ timeout: 4000 });
});

test('newsletter signup validates, then sends name, consent and the pages of the visit when analytics cookies are accepted', async ({ page, context }) => {
  await context.addCookies([{
    name: 'mt_cookie_consent_v2',
    value: encodeURIComponent(JSON.stringify({ necessary: true, analytics: true, marketing: false })),
    url: 'http://127.0.0.1:4173'
  }]);
  const sent = await mockNewsletter(page, { payload: { success: true, data: { message: 'Casi listo: revisa tu correo.' } } });
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=30&from=home');
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second');
  await expect(page.getByRole('dialog')).toBeVisible();

  await page.getByLabel('Nombre').fill('Ana');
  await page.getByLabel('Correo electrónico').fill('ana@example');
  await page.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(page.getByRole('status')).toHaveText('Escribe un correo electrónico válido.');
  await page.getByLabel('Correo electrónico').fill('ana@example.com');
  await page.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(page.getByRole('status')).toHaveText('Para suscribirte tienes que aceptar recibir nuestras comunicaciones.');
  expect(sent.body).toBe('');

  await page.getByRole('checkbox').check();
  await page.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(page.getByRole('status')).toHaveText('Casi listo: revisa tu correo.');
  expect(formField(sent.body, 'action')).toBe('mt_newsletter_subscribe');
  expect(formField(sent.body, 'name')).toBe('Ana');
  expect(formField(sent.body, 'email')).toBe('ana@example.com');
  expect(formField(sent.body, 'consent')).toBe('1');
  expect(formField(sent.body, 'website')).toBe('');
  expect(formField(sent.body, 'page')).toBe('/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second');
  const pages = JSON.parse(formField(sent.body, 'pages'));
  expect(pages.map((visited) => visited.u)).toEqual([
    '/tests/e2e/fixtures/newsletter.html?delay=30&from=home',
    '/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second'
  ]);
  expect(pages[0].t).toBe('Traslados aeropuerto');
  const state = await page.evaluate(() => JSON.parse(window.localStorage.getItem('mt_newsletter_state')));
  expect(state.status).toBe('subscribed');
});

test('without analytics consent only the signup page is sent and the visit is not stored in the browser', async ({ page }) => {
  const sent = await mockNewsletter(page);
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=30&from=home');
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second');
  await expect(page.getByRole('dialog')).toBeVisible();
  await page.getByLabel('Correo electrónico').fill('bob@example.com');
  await page.getByRole('checkbox').check();
  await page.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(page.getByRole('status')).toHaveText('OK');

  const pages = JSON.parse(formField(sent.body, 'pages'));
  expect(pages.map((visited) => visited.u)).toEqual(['/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second']);
  expect(await page.evaluate(() => window.sessionStorage.getItem('mt_newsletter_pages'))).toBeNull();
});

test('a server refusal is shown and the visitor can try again', async ({ page }) => {
  await mockNewsletter(page, { status: 429, payload: { success: false, data: { message: 'Demasiados intentos.' } } });
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.2');
  await page.getByLabel('Correo electrónico').fill('eve@example.com');
  await page.getByRole('checkbox').check();
  await page.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(page.getByRole('status')).toHaveText('Demasiados intentos.');
  await expect(page.getByRole('button', { name: 'Suscribirme' })).toBeEnabled();
  expect(await page.evaluate(() => window.localStorage.getItem('mt_newsletter_state'))).toBeNull();
});

test('newsletter popup has the website look over the theme stylesheet and fits a phone', async ({ page }) => {
  await mockNewsletter(page);
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.1');
  await expect(page.getByRole('dialog')).toBeVisible();
  // Same tokens as the live site: navy sections, amber eyebrow, white Outfit title, navy booking button.
  await expect(page.locator('.mt-nl__eyebrow')).toHaveText('Ofertas exclusivas');
  await expect(page.locator('.mt-nl__eyebrow')).toHaveCSS('color', 'rgb(255, 181, 71)');
  await expect(page.locator('.mt-nl__title')).toHaveCSS('color', 'rgb(255, 255, 255)');
  await expect(page.locator('.mt-nl__title')).toHaveCSS('font-family', /Outfit/);
  await expect(page.locator('.mt-nl__submit')).toHaveCSS('color', 'rgb(255, 255, 255)');
  await expect(page.locator('.mt-nl__submit')).toHaveCSS('background-color', 'rgb(11, 31, 53)');
  await expect(page.locator('.mt-nl__submit')).toHaveCSS('border-radius', '16px');
  await expect(page.getByLabel('Correo electrónico')).toHaveCSS('background-color', 'rgb(249, 250, 251)');
  await expect(page.getByLabel('Correo electrónico')).toHaveCSS('border-radius', '12px');
  const consent = page.locator('.mt-nl__consent');
  await expect(consent).toHaveText('Acepto recibir comunicaciones comerciales de MeTransfers y he leído la política de privacidad.');
  await expect(consent.getByRole('link')).toHaveText('política de privacidad');
  await expect(consent.getByRole('link')).toHaveAttribute('href', '/politica-de-privacidad/');

  await page.setViewportSize({ width: 375, height: 700 });
  // On a phone the popup is a bottom sheet; wait for its slide-in to finish.
  await expect.poll(async () => {
    const sheet = await page.getByRole('dialog').boundingBox();
    return Math.round(sheet.y + sheet.height);
  }).toBe(700);
  const box = await page.getByRole('dialog').boundingBox();
  expect(box.width).toBeLessThanOrEqual(375);
  // The eyebrow and its amber dash stay on one line next to the close button.
  const eyebrowLines = await page.locator('.mt-nl__eyebrow').evaluate((node) => Math.round(node.getBoundingClientRect().height / parseFloat(getComputedStyle(node).lineHeight)));
  expect(eyebrowLines).toBe(1);
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);
});

test('newsletter popup appears once per session and asks the server only once', async ({ page }) => {
  const calls = await mockNewsletter(page);
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&from=first');
  await expect(page.getByRole('dialog')).toBeVisible();
  expect(calls.slot).toBe(1);

  // The visitor ignores it and keeps browsing: no second popup and no second request in this session.
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&from=second');
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
  expect(calls.slot).toBe(1);
});

test('newsletter popup stays hidden when this IP already saw it and waits 24 hours before asking again', async ({ page }) => {
  const calls = await mockNewsletter(page, { slot: false });
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3');
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
  expect(calls.slot).toBe(1);

  // A new session (another tab) within 24 hours does not even ask the server.
  const tab = await page.context().newPage();
  await tab.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&tab=2');
  await tab.waitForTimeout(1500);
  expect(calls.slot).toBe(1);

  // 25 hours later, a new session asks once more.
  await tab.evaluate(() => window.localStorage.setItem('mt_newsletter_checked_at', String(Date.now() - 25 * 3600000)));
  const later = await page.context().newPage();
  await later.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3&tab=3');
  await expect.poll(() => calls.slot).toBe(2);
});

test('without an answer from the server the popup does not open', async ({ page }) => {
  await page.context().route('**/newsletter-endpoint', (route) => route.fulfill({ status: 500, body: 'error' }));
  await page.goto('/tests/e2e/fixtures/newsletter.html?delay=0.3');
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
});

test('footer newsletter block subscribes with the same rules and works where the popup never opens', async ({ page }) => {
  const calls = await mockNewsletter(page, { payload: { success: true, data: { message: 'Casi listo: revisa tu correo.' } } });
  await page.goto('/tests/e2e/newsletter-footer?popup=0');
  const block = page.getByRole('region', { name: 'Recibe nuestras ofertas por email' });
  await expect(block).toBeVisible();

  await block.getByLabel('Nombre').fill('Lucía');
  await block.getByLabel('Correo electrónico').fill('lucia@example.com');
  await block.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(block.getByRole('status')).toHaveText('Para suscribirte tienes que aceptar recibir nuestras comunicaciones.');
  expect(calls.body).toBe('');

  await block.getByRole('checkbox').check();
  await block.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(block.getByRole('status')).toHaveText('Casi listo: revisa tu correo.');
  await expect(block.locator('form')).toBeHidden();
  expect(formField(calls.body, 'source')).toBe('footer');
  expect(formField(calls.body, 'email')).toBe('lucia@example.com');
  expect(formField(calls.body, 'consent')).toBe('1');
  expect(formField(calls.body, 'website')).toBe('');
  expect(calls.slot).toBe(0);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);

  // Subscribed in the footer: on a page where the popup is allowed it no longer opens, nor asks the server.
  await page.goto('/tests/e2e/newsletter-footer?popup=1');
  await page.waitForTimeout(1500);
  await expect(page.locator('[data-mt-newsletter]')).toHaveCount(0);
  expect(calls.slot).toBe(0);
});

test('footer newsletter block shows a server refusal and lets the visitor try again', async ({ page }) => {
  await mockNewsletter(page, { status: 429, payload: { success: false, data: { message: 'Demasiados intentos.' } } });
  await page.goto('/tests/e2e/newsletter-footer?popup=0');
  const block = page.locator('[data-mt-newsletter-footer]');
  await block.getByLabel('Correo electrónico').fill('eve@example.com');
  await block.getByRole('checkbox').check();
  await block.getByRole('button', { name: 'Suscribirme' }).click();
  await expect(block.getByRole('status')).toHaveText('Demasiados intentos.');
  await expect(block.getByRole('button', { name: 'Suscribirme' })).toBeEnabled();
  await expect(block.locator('form')).toBeVisible();
});

test('footer newsletter block has the website look, one row on desktop and stacked on a phone', async ({ page }) => {
  await mockNewsletter(page);
  await page.setViewportSize({ width: 1280, height: 900 });
  await page.goto('/tests/e2e/newsletter-footer?popup=0');
  const block = page.locator('[data-mt-newsletter-footer]');
  await expect(block.locator('.mt-nlf__eyebrow')).toHaveCSS('color', 'rgb(255, 181, 71)');
  await expect(block.locator('.mt-nlf__title')).toHaveCSS('color', 'rgb(255, 255, 255)');
  await expect(block.locator('.mt-nlf__title')).toHaveCSS('font-family', /Outfit/);
  await expect(block.getByRole('button', { name: 'Suscribirme' })).toHaveCSS('background-color', 'rgb(0, 102, 204)');
  await expect(block.getByLabel('Correo electrónico')).toHaveCSS('background-color', 'rgb(249, 250, 251)');
  await expect(block.getByRole('link', { name: 'política de privacidad' })).toHaveAttribute('href', '/politica-de-privacidad/');

  const row = async () => Promise.all([
    block.getByLabel('Nombre').boundingBox(),
    block.getByLabel('Correo electrónico').boundingBox(),
    block.getByRole('button', { name: 'Suscribirme' }).boundingBox()
  ]);
  let [name, email, button] = await row();
  expect(Math.round(name.y)).toBe(Math.round(email.y));
  expect(Math.round(button.y + button.height)).toBe(Math.round(email.y + email.height));
  expect(email.x).toBeGreaterThan(name.x);

  await page.setViewportSize({ width: 375, height: 800 });
  [name, email, button] = await row();
  expect(email.y).toBeGreaterThan(name.y);
  expect(button.y).toBeGreaterThan(email.y);
  // On a phone the consent box sits between the fields and the button.
  const consent = await block.getByRole('checkbox').boundingBox();
  expect(consent.y).toBeGreaterThan(email.y);
  expect(button.y).toBeGreaterThan(consent.y);
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);
});

test('footer newsletter block speaks English on English pages', async ({ page }) => {
  await mockNewsletter(page);
  await page.goto('/tests/e2e/newsletter-footer?lang=en&popup=0');
  const block = page.getByRole('region', { name: 'Get our offers by email' });
  await expect(block.getByRole('button', { name: 'Subscribe' })).toBeVisible();
  await expect(block.getByRole('link', { name: 'privacy policy' })).toHaveAttribute('href', '/en/politica-de-privacidad/');
});

test('admin newsletter worker progresses pending batches and stops when the queue drains', async ({ page }) => {
  await page.clock.install();
  let requests = 0;
  await page.route('**/wp-admin/admin-ajax.php', async route => {
    const body = new URLSearchParams(route.request().postData());
    expect(body.get('action')).toBe('mt_nl_dispatch');
    expect(body.get('nonce')).toBe('admin-fixture');
    expect(route.request().method()).toBe('POST');
    requests += 1;
    await route.fulfill({ json: { success: true, data: { sent: 1, pending: requests === 1 ? 1 : 0, error: '' } } });
  });
  await page.goto('/tests/e2e/fixtures/i18n.html');
  await page.setContent('<div data-mt-nl-worker data-url="/wp-admin/admin-ajax.php" data-nonce="admin-fixture"><p data-mt-nl-worker-status role="status"></p></div>');
  await page.addScriptTag({ url: '/assets/js/newsletter-admin.js' });
  await expect(page.getByRole('status')).toContainText('1 pendientes');
  await page.clock.fastForward(30000);
  await expect(page.getByRole('status')).toContainText('Procesamiento terminado');
  expect(requests).toBe(2);
  await page.clock.fastForward(60000);
  expect(requests).toBe(2);
});

test('admin newsletter worker recovers from request errors and renders SMTP errors as text', async ({ page }) => {
  await page.clock.install();
  let requests = 0;
  await page.route('**/wp-admin/admin-ajax.php', async route => {
    requests += 1;
    if (requests === 1) {
      await route.fulfill({ status: 403, json: { success: false } });
      return;
    }
    await route.fulfill({ json: { success: true, data: { sent: 0, pending: 1, error: '<img src=x onerror=alert(1)> SMTP refused' } } });
  });
  await page.goto('/tests/e2e/fixtures/i18n.html');
  await page.setContent('<div data-mt-nl-worker data-url="/wp-admin/admin-ajax.php" data-nonce="admin-fixture"><p data-mt-nl-worker-status role="status"></p></div>');
  await page.addScriptTag({ url: '/assets/js/newsletter-admin.js' });
  await expect(page.getByRole('status')).toContainText('No se pudo comprobar');
  await page.clock.fastForward(30000);
  await expect(page.getByRole('status')).toContainText('SMTP refused');
  await expect(page.getByRole('status').locator('img')).toHaveCount(0);
});

async function mountWelcomeMonitor(page) {
  await page.goto('/tests/e2e/fixtures/i18n.html');
  await page.setContent('<section data-mt-welcome-monitor data-url="/wp-admin/admin-ajax.php" data-nonce="monitor-fixture" data-remaining="21600"><strong data-nl-count="manual_waiting">0</strong><strong data-nl-count="mail_processing">0</strong><strong data-nl-count="mail_sent">0</strong><span data-nl-next></span><span data-nl-last></span><span data-nl-countdown></span><button data-nl-refresh>Actualizar ahora</button><span data-nl-monitor-status role="status"></span><table><tbody data-nl-monitor-rows></tbody></table></section>');
  await page.addScriptTag({ url: '/assets/js/newsletter-monitor.js' });
}

function welcomeSnapshot(waiting = 11015, state = 'En cola / esperando reintento') {
  return { counts: { manual_waiting: waiting, mail_processing: 0, mail_sent: 0 }, next_manual: '17/10/2026 00:30:00', remaining_seconds: 21600, last_batch: 'Sin tanda anterior', updated_at: '16/10/2026 18:30:00', recent: [{ email: 'contact@example.test', state, attempts: 0, sent_at: '—', retry_at: '17/10/2026 00:30:00', error: '' }] };
}

test('welcome monitor polls real status every ten seconds and supports manual refresh without sending mail', async ({ page }) => {
  await page.clock.install();
  let requests = 0;
  await page.route('**/wp-admin/admin-ajax.php', async route => {
    const body = new URLSearchParams(route.request().postData());
    expect(body.get('action')).toBe('mt_nl_welcome_status');
    expect(body.get('nonce')).toBe('monitor-fixture');
    expect(route.request().method()).toBe('POST');
    requests += 1;
    const data = welcomeSnapshot(11016 - requests, requests === 1 ? 'En cola / esperando reintento' : 'Enviándose');
    if (requests >= 2) data.counts.mail_processing = 1;
    await route.fulfill({ json: { success: true, data } });
  });
  await mountWelcomeMonitor(page);
  await expect(page.locator('[data-nl-count="manual_waiting"]')).toHaveText('11015');
  await expect(page.locator('[data-nl-monitor-rows]')).toContainText('En cola / esperando reintento');
  await page.clock.fastForward(3000);
  await expect(page.locator('[data-nl-countdown]')).toContainText('5 h 59 min 57 s');
  expect(requests).toBe(1);
  await page.clock.fastForward(7000);
  await expect(page.locator('[data-nl-count="mail_processing"]')).toHaveText('1');
  await expect(page.locator('[data-nl-monitor-rows]')).toContainText('Enviándose');
  await page.getByRole('button', { name: 'Actualizar ahora' }).click();
  await expect(page.locator('[data-nl-count="manual_waiting"]')).toHaveText('11013');
  expect(requests).toBe(3);
});

test('welcome monitor retains its last counters on failure and escapes server error text', async ({ page }) => {
  await page.clock.install();
  let requests = 0;
  await page.route('**/wp-admin/admin-ajax.php', async route => {
    requests += 1;
    if (requests === 2) {
      await route.fulfill({ status: 503, json: { success: false } });
      return;
    }
    const data = welcomeSnapshot(7);
    data.remaining_seconds = 1;
    data.recent[0].error = '<img src=x onerror=alert(1)> SMTP refused';
    await route.fulfill({ json: { success: true, data } });
  });
  await mountWelcomeMonitor(page);
  await expect(page.locator('[data-nl-count="manual_waiting"]')).toHaveText('7');
  await expect(page.locator('[data-nl-monitor-rows]')).toContainText('SMTP refused');
  await expect(page.locator('[data-nl-monitor-rows] img')).toHaveCount(0);
  await page.clock.fastForward(1000);
  await expect(page.locator('[data-nl-countdown]')).toContainText('esperando ejecución del cron');
  await page.clock.fastForward(9000);
  await expect(page.getByRole('status')).toContainText('Se conservan los últimos datos');
  await expect(page.locator('[data-nl-count="manual_waiting"]')).toHaveText('7');
  await expect(page.getByRole('button', { name: 'Actualizar ahora' })).toBeEnabled();
});
