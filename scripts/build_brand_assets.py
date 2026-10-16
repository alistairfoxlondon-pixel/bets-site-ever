#!/usr/bin/env python3
"""Build the brand mark SVGs used across the product.

Every third party service the product mentions is rendered with its own
mark rather than plain text. The drawings are simplified, vector
recreations built from the published brand colours.
"""
import os

OUT = "/home/user/bets-site-ever/public/brand"
os.makedirs(OUT, exist_ok=True)

FONT = "font-family='\"Helvetica Neue\", Arial, sans-serif'"

ASSETS = {}

# ---------------------------------------------------------------- ad networks
ASSETS['adsense.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 40" role="img" aria-label="Google AdSense">
  <g transform="translate(0,0)">
    <path d="M20 4 L33 34 L25 34 L20 24 L15 34 L7 34 Z" fill="#4285f4"/>
    <path d="M20 4 L20 24 L7 34 Z" fill="#34a853"/>
    <path d="M20 4 L33 34 L20 24 Z" fill="#fbbc04"/>
    <path d="M12 30 L20 18 L28 30 L23 30 L20 25 L17 30 Z" fill="#ea4335"/>
  </g>
  <text x="42" y="26" font-size="17" font-weight="600" fill="#3c4043" {FONT} letter-spacing="-.2">AdSense</text>
</svg>
'''

ASSETS['adx.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 176 40" role="img" aria-label="Google Ad Manager">
  <g transform="translate(0,0)">
    <rect x="1" y="5" width="30" height="30" rx="4" fill="#ffffff" stroke="#c4d4e0" stroke-width="1.2"/>
    <path d="M7 29 L16 11 L25 29 L20 29 L16 21 L12 29 Z" fill="#1a73e8"/>
    <circle cx="16" cy="26" r="2.4" fill="#fbbc04"/>
  </g>
  <text x="40" y="20" font-size="13.5" font-weight="700" fill="#3c4043" {FONT} letter-spacing="-.1">Google</text>
  <text x="40" y="33" font-size="13.5" font-weight="500" fill="#5f6368" {FONT} letter-spacing="-.1">Ad Manager</text>
</svg>
'''

ASSETS['ezoic.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 40" role="img" aria-label="Ezoic">
  <g transform="translate(0,0)">
    <circle cx="20" cy="20" r="16" fill="#0b6b5b"/>
    <path d="M25 13.5c-1.6-1.3-3.5-2-5.6-2-4.9 0-8.4 3.6-8.4 8.5 0 5 3.6 8.5 8.6 8.5 2.2 0 4.1-.7 5.6-2v-5.4h-5.9v2.9h3v.6c-.7.4-1.5.6-2.4.6-3 0-5.1-2.2-5.1-5.2 0-2.9 2-5.2 5-5.2 1.4 0 2.6.5 3.5 1.4z" fill="#ffffff"/>
  </g>
  <text x="44" y="26" font-size="19" font-weight="700" fill="#0b6b5b" {FONT} letter-spacing="-.3">ezoic</text>
</svg>
'''

ASSETS['mediavine.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 178 40" role="img" aria-label="Mediavine">
  <g transform="translate(0,0)">
    <path d="M20 4c7 0 12 4.6 12 10.5C32 21 26.4 25 20 25 13.6 25 8 21 8 14.5 8 8.6 13 4 20 4z" fill="#2e933c"/>
    <path d="M20 36c2.6-6.6 6.4-11.6 11.4-15" stroke="#2e933c" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <path d="M13 29c-1.8-3-4-5.4-6.6-7" stroke="#2e933c" stroke-width="2.2" fill="none" stroke-linecap="round" opacity=".65"/>
  </g>
  <text x="42" y="26" font-size="18" font-weight="700" fill="#1f6f3f" {FONT} letter-spacing="-.3">mediavine</text>
</svg>
'''

ASSETS['raptive.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 164 40" role="img" aria-label="Raptive">
  <g transform="translate(0,0)">
    <rect x="2" y="4" width="32" height="32" rx="4" fill="#ff5a1f"/>
    <path d="M11 28V12h6.2c3.4 0 5.6 1.9 5.6 4.9 0 2.4-1.3 4-3.4 4.6L23 28h-3.8l-3-6h-2.6V28z" fill="#ffffff"/>
  </g>
  <text x="42" y="26" font-size="19" font-weight="700" fill="#e2491a" {FONT} letter-spacing="-.3">Raptive</text>
</svg>
'''

# ------------------------------------------------------------------ payments
ASSETS['stripe.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 40" role="img" aria-label="Stripe">
  <g transform="translate(0,0)">
    <rect x="1" y="5" width="30" height="30" rx="4" fill="#635bff"/>
    <path d="M11 22.4c0-1 .8-1.4 2.1-1.4 1.7 0 3.9.5 5.6 1.4v-5.3a10 10 0 0 0-5.1-1.2c-4.2 0-7 2.1-7 5.6 0 5.5 7.6 4.6 7.6 7 0 1.1-.9 1.5-2.3 1.5-1.9 0-4.4-.8-6.3-1.8v5.4c2.1.8 4.2 1.2 6.3 1.2 4.3 0 7.2-2 7.2-5.6 0-5.8-7.1-4.8-7.1-6.8z" fill="#ffffff"/>
  </g>
  <text x="40" y="26" font-size="18" font-weight="700" fill="#635bff" {FONT} letter-spacing="-.2">stripe</text>
</svg>
'''

ASSETS['paypal.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 158 40" role="img" aria-label="PayPal">
  <g transform="translate(0,0)">
    <path d="M12 6h6.6c3.5 0 5.6 2 5.1 5.4-.5 3.6-3 5.6-6.5 5.6h-2.2l-1 7.4H8z" fill="#003087"/>
    <path d="M20 12h6.6c3.5 0 5.6 2 5.1 5.4-.5 3.6-3 5.6-6.5 5.6H23l-1 7.4H16z" fill="#009cde" opacity=".92"/>
  </g>
  <text x="44" y="26" font-size="18" font-weight="700" fill="#003087" {FONT} letter-spacing="-.2">PayPal</text>
</svg>
'''

ASSETS['bkash.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 40" role="img" aria-label="bKash">
  <g transform="translate(0,0)">
    <rect x="1" y="4" width="32" height="32" rx="16" fill="#e2136e"/>
  </g>
  <text x="42" y="27" font-size="19" font-weight="700" fill="#e2136e" {FONT} letter-spacing="-.3">bKash</text>
</svg>
'''

ASSETS['nagad.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 152 40" role="img" aria-label="Nagad">
  <g transform="translate(0,0)">
    <circle cx="17" cy="20" r="16" fill="#f7941d"/>
    <path d="M11 13.5c1.2-1.4 2.8-2.1 4.6-2.1 3.2 0 5.5 2.4 5.5 5.4 0 4-3 7.3-6.6 7.3-2.6 0-4.4-1.8-4.4-4.2 0-2.2 1.5-3.9 3.5-3.9 1.7 0 3 1.2 3 2.8 0 1.2-.8 2.1-1.9 2.1" stroke="#ffffff" stroke-width="2" fill="none" stroke-linecap="round"/>
  </g>
  <text x="42" y="26" font-size="19" font-weight="700" fill="#e07f0d" {FONT} letter-spacing="-.3">Nagad</text>
</svg>
'''

ASSETS['sslcommerz.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 190 40" role="img" aria-label="SSLCommerz">
  <g transform="translate(0,0)">
    <rect x="1" y="6" width="28" height="28" rx="3" fill="#f26522"/>
    <path d="M15 12l-6 8h5l-1 8 6-9h-5z" fill="#ffffff"/>
  </g>
  <text x="38" y="20" font-size="13" font-weight="700" fill="#1f2b3a" {FONT}>SSL</text>
  <text x="38" y="33" font-size="13" font-weight="500" fill="#f26522" {FONT}>COMMERZ</text>
</svg>
'''

ASSETS['rocket.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 166 40" role="img" aria-label="Rocket by AamarPay">
  <g transform="translate(0,0)">
    <path d="M18 3c4.6 2.6 7.4 7.4 7.4 12.6 0 4.4-2.4 8.4-6 10.4l-1.6-6.4-4.4-1-6.4-1.6c2-3.6 6-6 10.4-6C22.6 10.4 20.6 5.6 18 3z" fill="#e02f2f"/>
    <circle cx="19.6" cy="12.4" r="2.6" fill="#ffffff"/>
  </g>
  <text x="42" y="26" font-size="19" font-weight="700" fill="#c62828" {FONT} letter-spacing="-.3">Rocket</text>
</svg>
'''

ASSETS['aamarpay.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 186 40" role="img" aria-label="AamarPay">
  <g transform="translate(0,0)">
    <circle cx="17" cy="20" r="15" fill="#1b6ac9"/>
    <path d="M10.5 25.5l6.5-13 6.5 13" stroke="#ffffff" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
  <text x="40" y="26" font-size="17" font-weight="700" fill="#1b6ac9" {FONT} letter-spacing="-.2">aamarPay</text>
</svg>
'''

ASSETS['razorpay.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 182 40" role="img" aria-label="Razorpay">
  <g transform="translate(0,0)">
    <rect x="1" y="5" width="30" height="30" rx="4" fill="#0c2451"/>
    <path d="M10 12h6.4c3.2 0 5.2 1.8 5.2 4.6 0 2.2-1.2 3.8-3.2 4.4L22 28h-3.6l-2.8-6.2h-2.2V28H10z" fill="#ffffff"/>
  </g>
  <text x="40" y="26" font-size="18" font-weight="700" fill="#0c2451" {FONT} letter-spacing="-.2">razorpay</text>
</svg>
'''

ASSETS['paymob.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 168 40" role="img" aria-label="Paymob">
  <g transform="translate(0,0)">
    <rect x="1" y="5" width="30" height="30" rx="6" fill="#6a2c8f"/>
    <circle cx="12" cy="20" r="3.4" fill="#ffffff"/>
    <circle cx="20" cy="20" r="3.4" fill="#ffffff" opacity=".7"/>
  </g>
  <text x="40" y="26" font-size="18" font-weight="700" fill="#6a2c8f" {FONT} letter-spacing="-.2">paymob</text>
</svg>
'''

ASSETS['cashfree.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 172 40" role="img" aria-label="Cashfree">
  <g transform="translate(0,0)">
    <rect x="1" y="5" width="30" height="30" rx="4" fill="#00a0e9"/>
    <path d="M21 12.6a8 8 0 1 0 0 14.8" stroke="#ffffff" stroke-width="3" fill="none" stroke-linecap="round"/>
  </g>
  <text x="40" y="26" font-size="18" font-weight="700" fill="#0090d0" {FONT} letter-spacing="-.2">cashfree</text>
</svg>
'''

ASSETS['flutterwave.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 196 40" role="img" aria-label="Flutterwave">
  <g transform="translate(0,0)">
    <path d="M4 12h8l5 14 5-14h8l-9 22h-8z" fill="#1a3e6e"/>
    <path d="M17 12h8l-4 9z" fill="#f9a825"/>
  </g>
  <text x="40" y="26" font-size="17" font-weight="700" fill="#1a3e6e" {FONT} letter-spacing="-.2">flutterwave</text>
</svg>
'''

ASSETS['bank.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 40" role="img" aria-label="Bank transfer">
  <g transform="translate(0,0)">
    <path d="M4 17L18 7l14 10v3H4z" fill="#12395a"/>
    <rect x="7" y="22" width="4" height="9" fill="#12395a"/>
    <rect x="15" y="22" width="4" height="9" fill="#12395a"/>
    <rect x="23" y="22" width="4" height="9" fill="#12395a"/>
    <rect x="4" y="33" width="28" height="3" fill="#12395a"/>
  </g>
  <text x="42" y="26" font-size="16" font-weight="600" fill="#12395a" {FONT}>Bank transfer</text>
</svg>
'''

# ------------------------------------------------------------------- product
ASSETS['adready.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" role="img" aria-label="AdReady">
  <rect width="40" height="40" rx="6" fill="#12395a"/>
  <path d="M20 9 L30 29 L24 29 L20 21.5 L16 29 L10 29 Z" fill="#ffffff"/>
  <rect x="16.2" y="24" width="7.6" height="2" fill="#7fb2d9"/>
</svg>
'''

ASSETS['adready-word.svg'] = f'''
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 190 40" role="img" aria-label="AdReady">
  <g transform="translate(0,0)">
    <rect x="0" y="2" width="36" height="36" rx="5" fill="#12395a"/>
    <path d="M18 8 L27 28 L21.5 28 L18 21 L14.5 28 L9 28 Z" fill="#ffffff"/>
    <rect x="14.5" y="23" width="7" height="1.8" fill="#7fb2d9"/>
  </g>
  <text x="46" y="27" font-size="22" font-weight="400" fill="#16202b" {FONT} letter-spacing="-.2">AdReady</text>
</svg>
'''

for name, body in ASSETS.items():
    with open(os.path.join(OUT, name), "w") as fh:
        fh.write(body.strip() + "\n")

print(f"wrote {len(ASSETS)} brand assets to {OUT}")
