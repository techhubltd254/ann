addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const url = new URL(request.url);
  const path = url.pathname;

  if (path.startsWith('/storage/')) {
    const key = path.replace('/storage/', '');
    const object = await MEDIA.get(key);
    if (!object) return new Response('Not Found', { status: 404 });
    const headers = new Headers();
    headers.set('content-type', object.httpMetadata?.contentType || 'application/octet-stream');
    headers.set('cache-control', 'public, max-age=31536000');
    return new Response(object.body, { headers });
  }

  // Serve SPA for all other routes (client-side routing)
  const object = await MEDIA.get('www/index.html');
  if (object) {
    const headers = new Headers();
    headers.set('content-type', 'text/html; charset=utf-8');
    headers.set('cache-control', 'public, max-age=300');
    return new Response(object.body, { headers });
  }

  return Response.redirect('https://kicc-platform.pages.dev' + path, 302);
}
