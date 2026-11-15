"""
Turns the generated logo (on a white background) into a transparent, tightly cropped mark.

    python3 logo_cutout.py logo-source.png logo-mark.png
"""
import sys

import numpy as np
from PIL import Image

src, out = sys.argv[1], sys.argv[2]
rgb = np.asarray(Image.open(src).convert('RGB')).astype(np.float64) / 255.0

# The edge pixels are the mark blended over white; un-blend them so the rim stays soft.
# Every colour in the mark is far from white, so anything past a small distance is solid
# and only the anti-aliased rim gets partial alpha.
alpha = np.max((1.0 - rgb), axis=2)
alpha = np.clip((alpha - 0.04) / 0.36, 0, 1)        # drop the faint off-white noise
safe = np.where(alpha > 0, alpha, 1)
color = np.clip(1.0 - (1.0 - rgb) / safe[..., None], 0, 1)

rgba = np.dstack([color, alpha]) * 255
img = Image.fromarray(rgba.round().astype(np.uint8), 'RGBA')

box = img.getchannel('A').point(lambda a: 255 if a > 8 else 0).getbbox()
img = img.crop(box)

# Square canvas with a little breathing room, so it centres well as an icon.
side = int(max(img.size) * 1.06)
canvas = Image.new('RGBA', (side, side), (0, 0, 0, 0))
canvas.paste(img, ((side - img.width) // 2, (side - img.height) // 2), img)
canvas.save(out)
print(out, canvas.size, 'mark', img.size)
