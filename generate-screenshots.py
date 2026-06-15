#!/usr/bin/env python3
"""
Generate realistic screenshots for Kayumanies Cake Shop
Creates desktop (1280x720) and mobile (750x1334) screenshots
"""

from PIL import Image, ImageDraw, ImageFont
import os

# Colors from the actual CSS
PRIMARY = (139, 69, 19)
PRIMARY_DARK = (107, 52, 16)
PRIMARY_LIGHT = (160, 82, 45)
GOLD = (255, 215, 0)
ACCENT = (255, 107, 107)
BG_WARM = (255, 248, 240)
BG_CREAM = (255, 245, 230)
TEXT_DARK = (44, 24, 16)
TEXT_GRAY = (102, 102, 102)
WHITE = (255, 255, 255)
FOOTER_BG = (44, 24, 16)

def get_font(size, bold=False):
    """Try to get a font, fall back to default"""
    font_paths = [
        "C:/Windows/Fonts/segoeui.ttf",
        "C:/Windows/Fonts/arial.ttf",
        "C:/Windows/Fonts/calibri.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/TTF/DejaVuSans.ttf",
    ]
    if bold:
        bold_paths = [
            "C:/Windows/Fonts/segoeuib.ttf",
            "C:/Windows/Fonts/arialbd.ttf",
            "C:/Windows/Fonts/calibrib.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "/usr/share/fonts/TTF/DejaVuSans-Bold.ttf",
        ]
        font_paths = bold_paths + font_paths

    for path in font_paths:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size)
            except:
                continue
    return ImageFont.load_default()


def rr(draw, x1, y1, x2, y2, radius, fill=None, outline=None, width=1):
    """Draw a rounded rectangle"""
    draw.rounded_rectangle([x1, y1, x2, y2], radius=radius, fill=fill, outline=outline, width=width)


def create_desktop_screenshot():
    """Create desktop screenshot 1280x720"""
    W, H = 1280, 720
    img = Image.new('RGB', (W, H), BG_WARM)
    draw = ImageDraw.Draw(img)

    # Browser chrome
    draw.rectangle([0, 0, W, 30], fill=(240, 240, 240))
    draw.ellipse([10, 10, 20, 20], fill=(255, 95, 87))
    draw.ellipse([24, 10, 34, 20], fill=(255, 189, 46))
    draw.ellipse([38, 10, 48, 20], fill=(39, 201, 63))
    rr(draw, 60, 6, 400, 24, 6, fill=WHITE)
    draw.text((70, 8), "\U0001f512 kayumanies.test", fill=TEXT_GRAY, font=get_font(10))

    # NAVBAR
    draw.rectangle([0, 30, W, 90], fill=WHITE)
    draw.line([0, 90, W, 90], fill=(230, 230, 230), width=1)

    f_logo = get_font(22, bold=True)
    draw.text((30, 46), "\U0001f382 Kayumanies", fill=PRIMARY, font=f_logo)

    f_nav = get_font(14)
    for i, item in enumerate(["Beranda", "Produk", "Kategori", "Tentang", "Kontak"]):
        draw.text((300 + i * 80, 52), item, fill=TEXT_DARK, font=f_nav)

    draw.ellipse([W - 100, 44, W - 70, 74], fill=BG_CREAM)
    draw.text((W - 92, 50), "\U0001f6d2", fill=PRIMARY, font=get_font(16))
    draw.ellipse([W - 75, 40, W - 58, 57], fill=ACCENT)
    draw.text((W - 70, 40), "3", fill=WHITE, font=get_font(10, bold=True))

    rr(draw, W - 55, 44, W - 10, 76, 18, fill=PRIMARY)
    draw.text((W - 48, 52), "Login", fill=WHITE, font=get_font(12, bold=True))

    # HERO
    draw.rectangle([0, 90, W, 370], fill=BG_CREAM)

    rr(draw, 60, 120, 260, 145, 15, fill=PRIMARY)
    draw.text((75, 124), "\u2728 Premium Quality Cake", fill=WHITE, font=get_font(12))

    f_h1 = get_font(36, bold=True)
    draw.text((60, 160), "Kue Lezat untuk", fill=TEXT_DARK, font=f_h1)
    draw.text((60, 200), "Momen Istimewa", fill=PRIMARY, font=f_h1)

    f_desc = get_font(14)
    draw.text((60, 245), "Nikmati kelezatan kue premium buatan tangan dengan bahan", fill=TEXT_GRAY, font=f_desc)
    draw.text((60, 265), "berkualitas terbaik. Setiap gigitan adalah kebahagiaan.", fill=TEXT_GRAY, font=f_desc)

    rr(draw, 60, 295, 210, 330, 20, fill=PRIMARY)
    draw.text((75, 302), "\U0001f6cd\ufe0f Belanja Sekarang", fill=WHITE, font=get_font(12, bold=True))

    rr(draw, 225, 295, 350, 330, 20, outline=PRIMARY, width=2)
    draw.text((240, 302), "\U0001f4cb Lihat Kategori", fill=PRIMARY, font=get_font(12, bold=True))

    for i, (num, label) in enumerate([("1000+", "Pelanggan Puas"), ("50+", "Varian Kue"), ("\u2b50 4.9", "Rating")]):
        x = 60 + i * 130
        draw.text((x, 345), num, fill=PRIMARY, font=get_font(18, bold=True))
        draw.text((x, 365), label, fill=TEXT_GRAY, font=get_font(11))

    draw.text((850, 130), "\U0001f382", fill=PRIMARY, font=get_font(160))

    # CATEGORIES
    y_cat = 400
    draw.text((60, y_cat), "Kategori Pilihan", fill=TEXT_DARK, font=get_font(22, bold=True))
    draw.text((60, y_cat + 30), "Pilih dari berbagai kategori kue spesial", fill=TEXT_GRAY, font=get_font(12))

    cats = [
        ("\U0001f382", "Kue Ulang Tahun", "Rayakan momen spesial"),
        ("\U0001f9c1", "Cupcake", "Manis dalam gigitan"),
        ("\U0001f36b", "Kue Coklat", "Untuk pecinta coklat"),
        ("\U0001f353", "Kue Buah", "Segar dan alami"),
        ("\U0001f380", "Kue Pengantin", "Cinta dalam lapisan"),
    ]
    for i, (icon, name, desc) in enumerate(cats):
        cx = 60 + i * 230
        rr(draw, cx, y_cat + 55, cx + 210, y_cat + 155, 12, fill=WHITE)
        draw.rounded_rectangle([cx + 2, y_cat + 57, cx + 208, y_cat + 153], 12, outline=(240, 240, 240), width=1)
        draw.text((cx + 85, y_cat + 65), icon, fill=PRIMARY, font=get_font(28))
        draw.text((cx + 55, y_cat + 100), name, fill=TEXT_DARK, font=get_font(13, bold=True))
        draw.text((cx + 30, y_cat + 122), desc, fill=TEXT_GRAY, font=get_font(10))

    # PRODUCTS
    y_prod = 580
    draw.rectangle([0, y_prod, W, H], fill=WHITE)
    draw.text((60, y_prod + 15), "Produk Unggulan", fill=TEXT_DARK, font=get_font(22, bold=True))
    draw.text((60, y_prod + 45), "Pilihan terbaik yang paling disukai", fill=TEXT_GRAY, font=get_font(12))

    prods = [
        ("\U0001f382", "Black Forest", "Rp 150.000", "Kue Ulang Tahun", True),
        ("\U0001f9c1", "Red Velvet", "Rp 85.000", "Cupcake", False),
        ("\U0001f36b", "Chocolate Lava", "Rp 120.000", "Kue Coklat", True),
        ("\U0001f353", "Strawberry Tart", "Rp 95.000", "Kue Buah", False),
    ]
    for i, (icon, name, price, cat, discount) in enumerate(prods):
        px = 60 + i * 295
        rr(draw, px, y_prod + 70, px + 275, y_prod + 210, 14, fill=BG_WARM)
        rr(draw, px + 10, y_prod + 80, px + 265, y_prod + 140, 10, fill=BG_CREAM)
        draw.text((px + 100, y_prod + 88), icon, fill=PRIMARY, font=get_font(36))

        if discount:
            rr(draw, px + 15, y_prod + 83, px + 75, y_prod + 100, 6, fill=ACCENT)
            draw.text((px + 20, y_prod + 84), "DISKON", fill=WHITE, font=get_font(8, bold=True))

        draw.text((px + 15, y_prod + 148), cat, fill=TEXT_GRAY, font=get_font(9))
        draw.text((px + 15, y_prod + 163), name, fill=TEXT_DARK, font=get_font(14, bold=True))
        draw.text((px + 15, y_prod + 183), price, fill=PRIMARY, font=get_font(14, bold=True))
        rr(draw, px + 175, y_prod + 178, px + 265, y_prod + 205, 8, fill=PRIMARY)
        draw.text((px + 185, y_prod + 185), "\U0001f6d2 Keranjang", fill=WHITE, font=get_font(9, bold=True))

    return img


def create_mobile_screenshot():
    """Create mobile PWA screenshot 750x1334"""
    W, H = 750, 1334
    img = Image.new('RGB', (W, H), BG_WARM)
    draw = ImageDraw.Draw(img)

    # Status bar
    draw.rectangle([0, 0, W, 50], fill=PRIMARY)
    draw.text((30, 15), "9:41", fill=WHITE, font=get_font(14, bold=True))
    draw.text((W - 80, 15), "\U0001f4f6 \U0001f50b", fill=WHITE, font=get_font(12))

    # NAVBAR
    draw.rectangle([0, 50, W, 110], fill=WHITE)
    draw.line([0, 110, W, 110], fill=(230, 230, 230), width=1)
    draw.text((20, 72), "\u2630", fill=TEXT_DARK, font=get_font(22))
    draw.text((60, 72), "\U0001f382 Kayumanies", fill=PRIMARY, font=get_font(20, bold=True))

    draw.ellipse([W - 70, 65, W - 40, 95], fill=BG_CREAM)
    draw.text((W - 62, 72), "\U0001f6d2", fill=PRIMARY, font=get_font(16))
    draw.ellipse([W - 50, 60, W - 33, 77], fill=ACCENT)
    draw.text((W - 45, 61), "3", fill=WHITE, font=get_font(10, bold=True))

    # HERO
    draw.rectangle([0, 110, W, 480], fill=BG_CREAM)
    rr(draw, 30, 135, 230, 160, 15, fill=PRIMARY)
    draw.text((45, 139), "\u2728 Premium Quality Cake", fill=WHITE, font=get_font(11))

    f_h1 = get_font(30, bold=True)
    draw.text((30, 175), "Kue Lezat untuk", fill=TEXT_DARK, font=f_h1)
    draw.text((30, 210), "Momen Istimewa", fill=PRIMARY, font=f_h1)

    f_desc = get_font(13)
    for j, line in enumerate(["Nikmati kelezatan kue premium", "buatan tangan dengan bahan", "berkualitas terbaik."]):
        draw.text((30, 255 + j * 20), line, fill=TEXT_GRAY, font=f_desc)

    draw.text((W - 200, 160), "\U0001f382", fill=PRIMARY, font=get_font(120))

    rr(draw, 30, 330, 220, 370, 20, fill=PRIMARY)
    draw.text((50, 338), "\U0001f6cd\ufe0f Belanja Sekarang", fill=WHITE, font=get_font(13, bold=True))
    rr(draw, 240, 330, 400, 370, 20, outline=PRIMARY, width=2)
    draw.text((255, 338), "\U0001f4cb Kategori", fill=PRIMARY, font=get_font(13, bold=True))

    for i, (num, label) in enumerate([("1000+", "Pelanggan"), ("50+", "Varian"), ("\u2b50 4.9", "Rating")]):
        x = 30 + i * 130
        draw.text((x, 390), num, fill=PRIMARY, font=get_font(18, bold=True))
        draw.text((x, 415), label, fill=TEXT_GRAY, font=get_font(11))

    # CATEGORIES
    y_cat = 500
    draw.text((30, y_cat), "Kategori Pilihan", fill=TEXT_DARK, font=get_font(20, bold=True))
    for i, (icon, name) in enumerate([("\U0001f382", "Ultah"), ("\U0001f9c1", "Cupcake"), ("\U0001f36b", "Coklat"), ("\U0001f353", "Buah"), ("\U0001f380", "Pengantin")]):
        cx = 30 + i * 145
        rr(draw, cx, y_cat + 40, cx + 130, y_cat + 130, 12, fill=WHITE)
        draw.rounded_rectangle([cx + 2, y_cat + 42, cx + 128, y_cat + 128], 12, outline=(240, 240, 240), width=1)
        draw.text((cx + 40, y_cat + 55), icon, fill=PRIMARY, font=get_font(30))
        draw.text((cx + 35, y_cat + 95), name, fill=TEXT_DARK, font=get_font(13, bold=True))

    # PRODUCTS
    y_prod = 660
    draw.rectangle([0, y_prod, W, H], fill=WHITE)
    draw.text((30, y_prod + 15), "Produk Unggulan", fill=TEXT_DARK, font=get_font(20, bold=True))

    prods = [
        ("\U0001f382", "Black Forest", "Rp 150.000", "Kue Ultah", True),
        ("\U0001f9c1", "Red Velvet", "Rp 85.000", "Cupcake", False),
        ("\U0001f36b", "Chocolate Lava", "Rp 120.000", "Kue Coklat", True),
        ("\U0001f353", "Strawberry Tart", "Rp 95.000", "Kue Buah", False),
    ]
    for i, (icon, name, price, cat, discount) in enumerate(prods):
        col = i % 2
        row = i // 2
        px = 30 + col * 350
        py = y_prod + 55 + row * 200

        rr(draw, px, py, px + 330, py + 180, 14, fill=BG_WARM)
        rr(draw, px + 10, py + 10, px + 320, py + 90, 10, fill=BG_CREAM)
        draw.text((px + 130, py + 20), icon, fill=PRIMARY, font=get_font(40))

        if discount:
            rr(draw, px + 15, py + 13, px + 75, py + 30, 6, fill=ACCENT)
            draw.text((px + 20, py + 14), "DISKON", fill=WHITE, font=get_font(8, bold=True))

        draw.text((px + 15, py + 100), cat, fill=TEXT_GRAY, font=get_font(10))
        draw.text((px + 15, py + 118), name, fill=TEXT_DARK, font=get_font(15, bold=True))
        draw.text((px + 15, py + 142), price, fill=PRIMARY, font=get_font(15, bold=True))
        rr(draw, px + 230, py + 135, px + 320, py + 170, 8, fill=PRIMARY)
        draw.text((px + 242, py + 143), "\U0001f6d2", fill=WHITE, font=get_font(14))

    # Bottom nav
    y_bottom = H - 90
    draw.rectangle([0, y_bottom, W, H], fill=WHITE)
    draw.line([0, y_bottom, W, y_bottom], fill=(230, 230, 230), width=1)

    for i, (icon, label, active) in enumerate([("\U0001f3e0", "Beranda", True), ("\U0001f50d", "Cari", False), ("\U0001f6d2", "Keranjang", False), ("\U0001f464", "Profil", False)]):
        bx = 30 + i * 180
        color = PRIMARY if active else TEXT_GRAY
        draw.text((bx + 20, y_bottom + 15), icon, fill=color, font=get_font(20))
        draw.text((bx + 15, y_bottom + 45), label, fill=color, font=get_font(11, bold=active))
        if active:
            draw.ellipse([bx + 35, y_bottom + 68, bx + 45, y_bottom + 78], fill=PRIMARY)

    rr(draw, W // 2 - 60, H - 8, W // 2 + 60, H - 2, 4, fill=(200, 200, 200))

    return img


def main():
    print("Generating Kayumanies screenshots...")
    out = "assets/images"
    os.makedirs(out, exist_ok=True)

    print("Creating desktop screenshot (1280x720)...")
    create_desktop_screenshot().save(os.path.join(out, "screenshot-desktop.png"), "PNG")
    print("  [OK] Saved: assets/images/screenshot-desktop.png")

    print("Creating mobile screenshot (750x1334)...")
    create_mobile_screenshot().save(os.path.join(out, "screenshot-mobile.png"), "PNG")
    print("  [OK] Saved: assets/images/screenshot-mobile.png")

    print("\n[DONE] Screenshots generated successfully!")
    print("   Desktop: 1280x720")
    print("   Mobile:  750x1334")


if __name__ == "__main__":
    main()
