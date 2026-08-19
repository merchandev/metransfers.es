import { createReadStream } from 'node:fs';
import { stat } from 'node:fs/promises';
import { createServer } from 'node:http';
import { dirname, extname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const mimeTypes = {
  '.css': 'text/css; charset=utf-8',
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml'
};

const server = createServer(async (request, response) => {
  try {
    const url = new URL(request.url || '/', 'http://127.0.0.1');
    const relativePath = decodeURIComponent(url.pathname).replace(/^\/+/, '');
    const filePath = resolve(root, relativePath || 'tests/e2e/fixtures/i18n.html');
    if (filePath !== root && !filePath.startsWith(root + sep)) {
      response.writeHead(403).end('Forbidden');
      return;
    }

    const details = await stat(filePath);
    if (!details.isFile()) {
      response.writeHead(404).end('Not found');
      return;
    }

    response.writeHead(200, {
      'Cache-Control': 'no-store',
      'Content-Type': mimeTypes[extname(filePath)] || 'application/octet-stream'
    });
    createReadStream(filePath).pipe(response);
  } catch (_error) {
    response.writeHead(404).end('Not found');
  }
});

server.listen(4173, '127.0.0.1');

for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => server.close(() => process.exit(0)));
}
