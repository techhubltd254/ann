export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const path = url.pathname;

    // Images from R2
    if (path.startsWith('/storage/')) {
      const key = path.replace('/storage/', '');
      const object = await env.MEDIA.get(key);
      if (!object) return Response.redirect('https://kicc-platform.pages.dev', 302);
      const headers = new Headers();
      object.writeHttpMetadata(headers);
      headers.set('cache-control', 'public, max-age=31536000');
      return new Response(object.body, { headers });
    }

    // Redirect to CF Pages (Supabase-powered, no laptop)
    return Response.redirect('https://kicc-platform.pages.dev' + path, 302);
  }
};
