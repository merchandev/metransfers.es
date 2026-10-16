/* Continue newsletter batches from the authenticated campaign screen. */
(() => {
  'use strict';
  const worker = document.querySelector('[data-mt-nl-worker]');
  if (!worker) return;
  const status = worker.querySelector('[data-mt-nl-worker-status]');
  if (!status) return;
  const tick = async () => {
    let pending = true;
    try {
      const body = new URLSearchParams({ action: 'mt_nl_dispatch', nonce: worker.dataset.nonce });
      const response = await fetch(worker.dataset.url, { method: 'POST', credentials: 'same-origin', body });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error('dispatch_failed');
      const data = result.data;
      pending = data.pending > 0;
      status.textContent = `${data.sent} enviados en esta comprobación · ${data.pending} pendientes.`;
      if (data.error) status.textContent += ` Último error: ${data.error}`;
      if (data.message) status.textContent += ` ${data.message}`;
      if (!pending) {
        status.textContent += ' Procesamiento terminado. Actualiza la página para ver los contadores y posibles fallos.';
      }
    } catch (_error) {
      status.textContent = 'No se pudo comprobar la cola. Se reintentará; también puedes usar «Procesar ahora una tanda».';
    }
    if (pending) window.setTimeout(tick, 30000);
  };
  tick();
})();
