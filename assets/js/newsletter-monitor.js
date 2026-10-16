/* Read-only live status for newsletter admissions and confirmation mail. */
(() => {
  'use strict';
  const panel = document.querySelector('[data-mt-welcome-monitor]');
  if (!panel) return;
  const status = panel.querySelector('[data-nl-monitor-status]');
  const refresh = panel.querySelector('[data-nl-refresh]');
  const countdown = panel.querySelector('[data-nl-countdown]');
  let remaining = panel.dataset.remaining === '' ? null : Number(panel.dataset.remaining);
  let receivedAt = Date.now();
  let busy = false;
  let timer;
  const updateCountdown = () => {
    if (remaining === null) { countdown.textContent = ''; return; }
    const seconds = Math.max(0, remaining - Math.floor((Date.now() - receivedAt) / 1000));
    if (seconds === 0) { countdown.textContent = 'Hora prevista alcanzada; esperando ejecución del cron.'; return; }
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    countdown.textContent = `Faltan ${hours} h ${minutes} min ${seconds % 60} s`;
  };
  const poll = async () => {
    if (busy) return;
    busy = true;
    window.clearTimeout(timer);
    refresh.disabled = true;
    try {
      const body = new URLSearchParams({ action: 'mt_nl_welcome_status', nonce: panel.dataset.nonce });
      const response = await fetch(panel.dataset.url, { method: 'POST', credentials: 'same-origin', body, cache: 'no-store' });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error('status_unavailable');
      const data = result.data;
      panel.querySelectorAll('[data-nl-count]').forEach(node => { node.textContent = String(data.counts[node.dataset.nlCount] ?? 0); });
      panel.querySelector('[data-nl-next]').textContent = data.next_manual;
      panel.querySelector('[data-nl-last]').textContent = data.last_batch;
      const rows = panel.querySelector('[data-nl-monitor-rows]');
      rows.replaceChildren();
      data.recent.forEach(item => {
        const row = document.createElement('tr');
        ['email', 'state', 'attempts', 'sent_at', 'retry_at', 'error'].forEach(key => {
          const cell = document.createElement('td');
          cell.style.overflowWrap = 'anywhere';
          cell.textContent = String(item[key] ?? '');
          row.append(cell);
        });
        rows.append(row);
      });
      if (!data.recent.length) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 6;
        cell.textContent = 'Aún no hay contactos incorporados. Los que esperan su tanda se cuentan arriba.';
        row.append(cell);
        rows.append(row);
      }
      remaining = data.remaining_seconds;
      receivedAt = Date.now();
      updateCountdown();
      status.textContent = `Última actualización: ${data.updated_at}`;
    } catch (_error) {
      status.textContent = 'No se pudo actualizar. Se conservan los últimos datos; se reintentará en 10 segundos.';
    } finally {
      busy = false;
      refresh.disabled = false;
      timer = window.setTimeout(poll, 10000);
    }
  };
  refresh.addEventListener('click', poll);
  updateCountdown();
  window.setInterval(updateCountdown, 1000);
  poll();
})();
