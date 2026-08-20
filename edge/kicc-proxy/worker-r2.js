export default {
  async fetch(request, env) {
    const url = new URL(request.url)
    const path = url.pathname.replace(/^\/storage\//, '')

    // Handle CORS preflight
    if (request.method === 'OPTIONS') {
      return new Response(null, {
        headers: {
          'Access-Control-Allow-Origin': '*',
          'Access-Control-Allow-Methods': 'GET, HEAD, OPTIONS',
          'Access-Control-Allow-Headers': 'Range, Accept, Content-Type, Origin',
          'Access-Control-Expose-Headers': 'Content-Type, Content-Length, Content-Range, Accept-Ranges',
          'Access-Control-Max-Age': '86400',
        },
      })
    }

    const object = await env.KICC_MEDIA.get(path)
    if (!object) return new Response('Not Found', { status: 404 })

    const headers = new Headers()
    object.writeHttpMetadata(headers)
    headers.set('cache-control', 'public, max-age=31536000, immutable')
    headers.set('accept-ranges', 'bytes')
    headers.set('Access-Control-Allow-Origin', '*')
    headers.set('Access-Control-Expose-Headers', 'Content-Type, Content-Length, Content-Range, Accept-Ranges')

    const range = request.headers.get('range')
    if (range) {
      const m = range.match(/bytes=(\d+)-(\d*)/)
      if (m) {
        const start = +m[1]
        const end = m[2] ? +m[2] : object.size - 1
        const r = await env.KICC_MEDIA.get(path, { range: { offset: start, length: end - start + 1 } })
        if (r) {
          headers.set('content-range', 'bytes ' + start + '-' + end + '/' + object.size)
          headers.set('content-length', '' + (end - start + 1))
          return new Response(r.body, { status: 206, headers })
        }
      }
    }

    return new Response(object.body, { headers })
  },
}