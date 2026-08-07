// Cloudflare Worker — routes traffic to KICC engine
// Deploy via: wrangler deploy

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url)
    
    // Route /api/* and web assets to our engine
    const ENGINE = env.ENGINE_URL || 'http://localhost:8091'
    
    const target = new URL(url.pathname + url.search, ENGINE)
    const response = await fetch(target, {
      method: request.method,
      headers: request.headers,
      body: request.body
    })
    
    // Add CORS
    const modified = new Response(response.body, response)
    modified.headers.set('Access-Control-Allow-Origin', '*')
    modified.headers.set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,PATCH,OPTIONS')
    modified.headers.set('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Backup-Key')
    return modified
  }
}
