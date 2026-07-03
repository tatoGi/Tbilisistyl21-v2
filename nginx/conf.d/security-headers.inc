# Shared security response headers — included at `server` scope by each server
# block. HSTS is intentionally NOT here: it must only be sent over HTTPS, so the
# TLS server block adds it separately.
#
# Note: nginx `add_header` does not merge across contexts — a `location` that
# sets its own `add_header` (e.g. /storage Cache-Control) drops these for that
# location. The main app responses (location /) inherit them, which is what the
# security scanners check.

server_tokens off;

add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=(), browsing-topics=()" always;

# CSP starts in Report-Only so it cannot break the Next.js site or the Filament
# admin (both rely on inline scripts/styles). Watch the browser console for
# violations under real use, then rename the header to `Content-Security-Policy`
# to enforce it.
add_header Content-Security-Policy-Report-Only "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'" always;
