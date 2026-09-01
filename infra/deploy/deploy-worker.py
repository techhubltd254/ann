import json, os, subprocess, sys

meta = json.load(open(os.path.dirname(__file__) + "/worker-meta.json"))
code = open(os.path.dirname(__file__) + "/../../edge/kicctest-gateway.js").read()
boundary = "----kicc" + os.urandom(8).hex()
body = b"--" + boundary.encode() + b"\r\n"
body += b'Content-Disposition: form-data; name="metadata"\r\nContent-Type: application/json\r\n\r\n'
body += json.dumps(meta).encode() + b"\r\n"
body += b"--" + boundary.encode() + b"\r\n"
body += b'Content-Disposition: form-data; name="kicctest-gateway.js"; filename="kicctest-gateway.js"\r\nContent-Type: application/javascript+module\r\n\r\n'
body += code.encode() + b"\r\n"
body += b"--" + boundary.encode() + b"--\r\n"
result = subprocess.run([
    "curl", "-s", "-X", "PUT",
    "https://api.cloudflare.com/client/v4/accounts/" + os.environ["CF_ACCOUNT"] + "/workers/scripts/kicctest-gateway",
    "-H", "Authorization: Bearer " + os.environ["CF_TOKEN"],
    "-H", "Content-Type: multipart/form-data; boundary=" + boundary,
    "--data-binary", "@-"
], input=body, capture_output=True)
resp = json.loads(result.stdout)
if resp.get("success"):
    sys.exit(0)
else:
    print("worker deploy FAILED: " + str(resp.get("errors", "unknown")))
    sys.exit(1)