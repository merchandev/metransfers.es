/**
 * MeTransfers newsletter popup.
 *
 * - Opens after the configured delay (10 s by default), never on top of the
 *   cookie banner and never again once the visitor subscribed or closed it
 *   (until the dismiss period ends).
 * - At most once per session and once per period (24 h by default) per
 *   browser and per IP: the browser asks the server only when the popup is
 *   due, and the server says yes once per IP per period.
 * - Remembers the pages of the visit in sessionStorage only when the visitor
 *   accepted analytics cookies; otherwise only the signup page is sent.
 */
(function () {
  'use strict';

  var config = window.mtNewsletter;
  if (!config || !config.texts) {
    return;
  }

  var STATE_KEY = 'mt_newsletter_state';
  var PAGES_KEY = 'mt_newsletter_pages';
  var SESSION_KEY = 'mt_newsletter_session';
  var CHECK_KEY = 'mt_newsletter_checked_at';
  var MAX_PAGES = 30;
  var loadedAt = Date.now();
  var texts = config.texts;
  var dialog = null;
  var lastFocus = null;

  function store(kind) {
    try {
      var area = window[kind];
      area.setItem('__mt_nl', '1');
      area.removeItem('__mt_nl');
      return area;
    } catch (error) {
      return null;
    }
  }

  function readJson(area, key, fallback) {
    if (!area) {
      return fallback;
    }
    try {
      var value = JSON.parse(area.getItem(key));
      return value === null ? fallback : value;
    } catch (error) {
      return fallback;
    }
  }

  function writeJson(area, key, value) {
    if (!area) {
      return;
    }
    try {
      area.setItem(key, JSON.stringify(value));
    } catch (error) {
      // Storage full or blocked: the popup still works for this page.
    }
  }

  function analyticsAllowed() {
    var match = document.cookie.match(/(?:^|;\s*)mt_cookie_consent_v2=([^;]+)/);
    if (!match) {
      return false;
    }
    try {
      return JSON.parse(decodeURIComponent(match[1])).analytics === true;
    } catch (error) {
      return false;
    }
  }

  function currentPage() {
    return {
      u: window.location.pathname + window.location.search,
      t: document.title.slice(0, 150),
      at: new Date().toISOString()
    };
  }

  function recordPage() {
    if (!analyticsAllowed()) {
      return;
    }
    var session = store('sessionStorage');
    var pages = readJson(session, PAGES_KEY, []);
    if (!Array.isArray(pages)) {
      pages = [];
    }
    var page = currentPage();
    var last = pages[pages.length - 1];
    if (!last || last.u !== page.u) {
      pages.push(page);
    }
    writeJson(session, PAGES_KEY, pages.slice(-MAX_PAGES));
  }

  function visitedPages() {
    var pages = analyticsAllowed() ? readJson(store('sessionStorage'), PAGES_KEY, []) : [];
    return Array.isArray(pages) && pages.length ? pages : [currentPage()];
  }

  function remembered() {
    var state = readJson(store('localStorage'), STATE_KEY, null);
    if (!state || typeof state !== 'object') {
      return false;
    }
    return state.status === 'subscribed' || (state.status === 'dismissed' && Number(state.until) > Date.now());
  }

  function remember(status) {
    writeJson(store('localStorage'), STATE_KEY, {
      status: status,
      until: Date.now() + Math.max(1, Number(config.dismissDays) || 14) * 86400000
    });
  }

  function element(tag, attributes, text) {
    var node = document.createElement(tag);
    Object.keys(attributes || {}).forEach(function (name) {
      node.setAttribute(name, attributes[name]);
    });
    if (text) {
      node.textContent = text;
    }
    return node;
  }

  function field(id, label, type, autocomplete) {
    var wrapper = element('div', { class: 'mt-nl__field' });
    wrapper.appendChild(element('label', { for: id }, label));
    var input = element('input', { id: id, name: id.replace('mt-nl-', ''), type: type, autocomplete: autocomplete });
    if (type === 'email') {
      input.required = true;
      input.setAttribute('inputmode', 'email');
      input.setAttribute('maxlength', '191');
    } else {
      input.setAttribute('maxlength', '120');
    }
    wrapper.appendChild(input);
    return wrapper;
  }

  // The words "política de privacidad" inside the consent text become the
  // link; a custom text without them gets the link appended.
  function consentLabel() {
    var text = String(texts.consent || '');
    var label = String(texts.privacy || '');
    var span = element('span');
    var at = label ? text.toLowerCase().indexOf(label.toLowerCase()) : -1;
    var link = element('a', { href: config.privacyUrl, target: '_blank', rel: 'noopener' });
    if (at < 0) {
      span.appendChild(document.createTextNode(text + ' '));
      link.textContent = label;
      span.appendChild(link);
      return span;
    }
    span.appendChild(document.createTextNode(text.slice(0, at)));
    link.textContent = text.slice(at, at + label.length);
    span.appendChild(link);
    span.appendChild(document.createTextNode(text.slice(at + label.length)));
    return span;
  }

  function build() {
    var overlay = element('div', { class: 'mt-nl', 'data-mt-newsletter': '' });
    var box = element('div', { class: 'mt-nl__dialog', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'mt-nl-title', 'aria-describedby': 'mt-nl-text' });
    // Navy head like the site's dark sections: amber eyebrow, white title.
    var head = element('div', { class: 'mt-nl__head' });
    var close = element('button', { type: 'button', class: 'mt-nl__close', 'aria-label': texts.close });
    close.innerHTML = '<span aria-hidden="true">&times;</span>';
    head.appendChild(close);
    if (texts.eyebrow) {
      head.appendChild(element('p', { class: 'mt-nl__eyebrow' }, texts.eyebrow));
    }
    head.appendChild(element('h2', { id: 'mt-nl-title', class: 'mt-nl__title' }, texts.title));
    head.appendChild(element('p', { id: 'mt-nl-text', class: 'mt-nl__text' }, texts.text));
    box.appendChild(head);

    var body = element('div', { class: 'mt-nl__body' });
    var form = element('form', { class: 'mt-nl__form', novalidate: '' });
    form.appendChild(field('mt-nl-name', texts.name, 'text', 'given-name'));
    form.appendChild(field('mt-nl-email', texts.email, 'email', 'email'));

    // Honeypot: hidden from people and assistive technology, filled by bots.
    var trap = element('div', { class: 'mt-nl__trap', 'aria-hidden': 'true' });
    trap.appendChild(element('input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off' }));
    form.appendChild(trap);

    var consent = element('label', { class: 'mt-nl__consent' });
    var checkbox = element('input', { type: 'checkbox', name: 'consent', value: '1', required: '' });
    consent.appendChild(checkbox);
    consent.appendChild(consentLabel());
    form.appendChild(consent);

    var submit = element('button', { type: 'submit', class: 'mt-nl__submit' }, texts.button);
    form.appendChild(submit);
    body.appendChild(form);
    body.appendChild(element('p', { class: 'mt-nl__status', role: 'status', 'aria-live': 'polite' }));
    box.appendChild(body);
    overlay.appendChild(box);

    close.addEventListener('click', function () { hide(true); });
    overlay.addEventListener('mousedown', function (event) {
      if (event.target === overlay) {
        hide(true);
      }
    });
    form.addEventListener('submit', send);
    overlay.addEventListener('keydown', keys);
    return overlay;
  }

  function focusables() {
    return Array.prototype.filter.call(
      dialog.querySelectorAll('button, a[href], input:not([tabindex="-1"])'),
      function (node) { return !node.disabled && node.offsetParent !== null; }
    );
  }

  function keys(event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      hide(true);
      return;
    }
    if (event.key !== 'Tab') {
      return;
    }
    var nodes = focusables();
    if (!nodes.length) {
      return;
    }
    var first = nodes[0];
    var last = nodes[nodes.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function setStatus(node, message, isError) {
    node.textContent = message;
    node.classList.toggle('is-error', !!isError);
  }

  // Shared by the popup and the footer block: validation, request and outcome.
  function submitSignup(form, source, statusNode, onDone) {
    var email = form.elements.email.value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setStatus(statusNode, texts.invalid_email, true);
      form.elements.email.focus();
      return;
    }
    if (!form.elements.consent.checked) {
      setStatus(statusNode, texts.need_consent, true);
      form.elements.consent.focus();
      return;
    }

    var submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    submit.textContent = texts.sending;
    setStatus(statusNode, '', false);

    var data = new FormData();
    data.append('action', config.action);
    data.append('source', source);
    data.append('name', form.elements.name.value.trim());
    data.append('email', email);
    data.append('consent', '1');
    data.append('website', form.elements.website.value);
    data.append('language', config.language);
    data.append('page', window.location.pathname + window.location.search);
    data.append('referrer', document.referrer || '');
    data.append('pages', JSON.stringify(visitedPages()));
    data.append('elapsed', String(Date.now() - loadedAt));

    fetch(config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) {
        return response.json().catch(function () { return { success: false }; });
      })
      .then(function (body) {
        var message = body && body.data && body.data.message;
        if (!body || !body.success) {
          throw new Error(message || texts.error);
        }
        // Subscribed from either place: the popup will not open again.
        remember('subscribed');
        form.hidden = true;
        setStatus(statusNode, message || texts.success_pending, false);
        onDone();
        if (window.dataLayer && typeof window.dataLayer.push === 'function') {
          window.dataLayer.push({ event: 'newsletter_signup', newsletter_source: source });
        }
      })
      .catch(function (error) {
        submit.disabled = false;
        submit.textContent = texts.button;
        setStatus(statusNode, error && error.message ? error.message : texts.error, true);
      });
  }

  function send(event) {
    event.preventDefault();
    submitSignup(event.currentTarget, 'popup', dialog.querySelector('.mt-nl__status'), function () {
      dialog.querySelector('.mt-nl__close').focus();
    });
  }

  // Footer block rendered by PublicController::footerForm().
  function bindFooter() {
    var block = document.querySelector('[data-mt-newsletter-footer]');
    if (!block) {
      return;
    }
    var form = block.querySelector('.mt-nlf__form');
    var statusNode = block.querySelector('.mt-nlf__status');
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitSignup(form, 'footer', statusNode, function () {
        statusNode.setAttribute('tabindex', '-1');
        statusNode.focus();
      });
    });
  }

  function show() {
    if (dialog || remembered()) {
      return;
    }
    lastFocus = document.activeElement;
    dialog = build();
    document.body.appendChild(dialog);
    document.body.classList.add('mt-nl-open');
    window.requestAnimationFrame(function () {
      dialog.classList.add('is-visible');
      dialog.querySelector('#mt-nl-name').focus();
    });
  }

  function hide(dismissed) {
    if (!dialog) {
      return;
    }
    if (dismissed && !remembered()) {
      remember('dismissed');
    }
    dialog.remove();
    dialog = null;
    document.body.classList.remove('mt-nl-open');
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  // Wait while the cookie banner or another overlay (menu, language picker) is open.
  function busy() {
    var banner = document.getElementById('mt-cookie-banner');
    return (banner && banner.classList.contains('show')) ||
      document.body.classList.contains('mt-lang-open') ||
      document.body.classList.contains('has-open-drawer') ||
      !!document.querySelector('#mob-menu.open');
  }

  // Once per session, and once per frequency period on this browser. Both are
  // recorded before asking the server, so a visitor triggers at most one
  // request per period even if it fails.
  function seenRecently() {
    var session = store('sessionStorage');
    if (session && session.getItem(SESSION_KEY)) {
      return true;
    }
    var last = Number(readJson(store('localStorage'), CHECK_KEY, 0));
    return last > 0 && Date.now() - last < Math.max(1, Number(config.hours) || 24) * 3600000;
  }

  function markChecked() {
    var session = store('sessionStorage');
    if (session) {
      try {
        session.setItem(SESSION_KEY, '1');
      } catch (error) {
        // Blocked storage: the server-side limit per IP still applies.
      }
    }
    writeJson(store('localStorage'), CHECK_KEY, Date.now());
  }

  // The server answers yes once per IP per period; any failure means no popup.
  function allowedByServer() {
    if (!config.slotAction || typeof window.fetch !== 'function') {
      return Promise.resolve(false);
    }
    var data = new FormData();
    data.append('action', config.slotAction);
    return fetch(config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (body) { return !!(body && body.success && body.data && body.data.show); })
      .catch(function () { return false; });
  }

  function openWhenFree() {
    if (remembered() || seenRecently()) {
      return;
    }
    if (busy()) {
      window.setTimeout(openWhenFree, 2000);
      return;
    }
    markChecked();
    allowedByServer().then(function (allowed) {
      if (allowed) {
        show();
      }
    });
  }

  function start() {
    recordPage();
    document.addEventListener('mt_analytics_granted', recordPage);
    bindFooter();
    // Booking and payment pages load the script only for the footer block.
    if (config.popup === false || remembered() || seenRecently()) {
      return;
    }
    window.setTimeout(openWhenFree, Math.max(0, Number(config.delay) || 10) * 1000);
  }

  window.mtNewsletterOpen = show;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
}());
