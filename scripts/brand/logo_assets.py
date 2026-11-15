"""
Writes the brand mark everywhere it is used (SVG logos, favicons, touch and app icons)
from logo-mark.png, the transparent cut-out made by logo_cutout.py.

    python3 scripts/brand/logo_assets.py
"""
import base64
import io
import json
from pathlib import Path

from PIL import Image

here = Path(__file__).resolve().parent
root = here.parents[1]
mark = Image.open(here / 'logo-mark.png').convert('RGBA')


def resized(size):
    return mark.resize((size, size), Image.LANCZOS)


def png_bytes(img):
    buffer = io.BytesIO()
    img.save(buffer, 'PNG', optimize=True)
    return buffer.getvalue()


def on_background(size, scale=0.8, color=(255, 255, 255, 255)):
    canvas = Image.new('RGBA', (size, size), color)
    inner = resized(int(size * scale))
    offset = (size - inner.width) // 2
    canvas.alpha_composite(inner, (offset, offset))
    return canvas


def write(path, data):
    target = root / path
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
    print('wrote', path, len(data))


# A palette PNG keeps the logo light; the mark only has a handful of flat colours.
encoded = base64.b64encode(png_bytes(resized(256).quantize(256, method=Image.Quantize.FASTOCTREE))).decode()
svg = (
    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
    'viewBox="0 0 256 256" width="256" height="256">'
    f'<image width="256" height="256" href="data:image/png;base64,{encoded}" '
    f'xlink:href="data:image/png;base64,{encoded}"/></svg>\n'
).encode()

for path in [
    'packages/Webkul/Admin/src/Resources/assets/images/logo.svg',
    'packages/Webkul/Admin/src/Resources/assets/images/dark-logo.svg',
    'packages/Webkul/Admin/src/Resources/assets/images/mobile-light-logo.svg',
    'packages/Webkul/Admin/src/Resources/assets/images/mobile-dark-logo.svg',
    'packages/Webkul/Installer/src/Resources/assets/images/logo.svg',
    'public/platform/mark.svg',
    'public/favicon.svg',
]:
    write(path, svg)

ico = io.BytesIO()
resized(256).save(ico, 'ICO', sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])

for path in [
    'packages/Webkul/Admin/src/Resources/assets/images/favicon.ico',
    'packages/Webkul/Installer/src/Resources/assets/images/favicon.ico',
    'public/favicon.ico',
    'public/platform/favicon.ico',
]:
    write(path, ico.getvalue())

write('public/apple-touch-icon.png', png_bytes(on_background(180, 0.78).convert('RGB')))
write('public/icon-192.png', png_bytes(on_background(192, 0.78)))
write('public/icon-512.png', png_bytes(on_background(512, 0.78)))
write('public/site.webmanifest', (json.dumps({
    'name': 'CRM',
    'short_name': 'CRM',
    'icons': [
        {'src': '/icon-192.png', 'sizes': '192x192', 'type': 'image/png'},
        {'src': '/icon-512.png', 'sizes': '512x512', 'type': 'image/png'},
    ],
    'theme_color': '#2F6BFF',
    'background_color': '#ffffff',
    'display': 'standalone',
    'start_url': '/admin',
}, indent=4) + '\n').encode())
