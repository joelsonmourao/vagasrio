#!/usr/bin/env python3
"""Generate favicon.ico, apple-touch-icon.png and logo-vagas-rj.png for Vagas RJ."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
PUBLIC = ROOT / "public"
IMG = PUBLIC / "assets" / "img"

NAVY = (6, 20, 40)
BLUE = (37, 99, 235)
WHITE = (255, 255, 255)
MUTED = (90, 106, 130)


def lerp(a: int, b: int, t: float) -> int:
    return int(a + (b - a) * t)


def gradient_rect(size: int) -> Image.Image:
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    radius = max(4, size // 4)
    for y in range(size):
        for x in range(size):
            if x < radius and y < radius and (radius - x) ** 2 + (radius - y) ** 2 > radius ** 2:
                continue
            if x >= size - radius and y < radius and (x - (size - radius - 1)) ** 2 + (radius - y) ** 2 > radius ** 2:
                continue
            if x < radius and y >= size - radius and (radius - x) ** 2 + (y - (size - radius - 1)) ** 2 > radius ** 2:
                continue
            if x >= size - radius and y >= size - radius and (x - (size - radius - 1)) ** 2 + (y - (size - radius - 1)) ** 2 > radius ** 2:
                continue
            t = (x + y) / (2 * (size - 1))
            color = (
                lerp(NAVY[0], BLUE[0], t),
                lerp(NAVY[1], BLUE[1], t),
                lerp(NAVY[2], BLUE[2], t),
                255,
            )
            draw.point((x, y), fill=color)
    return img


def load_font(size: int, bold: bool = True) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def draw_rj_icon(size: int) -> Image.Image:
    img = gradient_rect(size)
    draw = ImageDraw.Draw(img)
    font = load_font(max(10, size // 3), bold=True)
    text = "RJ"
    bbox = draw.textbbox((0, 0), text, font=font)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    draw.text(((size - tw) / 2 - bbox[0], (size - th) / 2 - bbox[1] - size * 0.02), text, fill=WHITE, font=font)
    return img


def draw_logo_png() -> Image.Image:
    width, height = 512, 512
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    mark_size = 220
    mark = draw_rj_icon(mark_size)
    img.paste(mark, ((width - mark_size) // 2, 70), mark)

    title_font = load_font(54, bold=True)
    subtitle_font = load_font(24, bold=False)
    title = "Vagas RJ"
    subtitle = "Empregos no Rio de Janeiro"

    tb = draw.textbbox((0, 0), title, font=title_font)
    tw = tb[2] - tb[0]
    draw.text(((width - tw) / 2 - tb[0], 320), title, fill=NAVY, font=title_font)

    sb = draw.textbbox((0, 0), subtitle, font=subtitle_font)
    sw = sb[2] - sb[0]
    draw.text(((width - sw) / 2 - sb[0], 390), subtitle, fill=MUTED, font=subtitle_font)

    return img


def main() -> None:
    IMG.mkdir(parents=True, exist_ok=True)

    icon_32 = draw_rj_icon(32)
    icon_16 = draw_rj_icon(16)
    icon_48 = draw_rj_icon(48)
    icon_180 = draw_rj_icon(180)
    logo = draw_logo_png()

    icon_32.save(PUBLIC / "favicon.ico", format="ICO", sizes=[(16, 16), (32, 32), (48, 48)])
    icon_180.save(PUBLIC / "apple-touch-icon.png", format="PNG", optimize=True)
    logo.save(IMG / "logo-vagas-rj.png", format="PNG", optimize=True)

    print("Generated:", PUBLIC / "favicon.ico")
    print("Generated:", PUBLIC / "apple-touch-icon.png")
    print("Generated:", IMG / "logo-vagas-rj.png")


if __name__ == "__main__":
    main()
