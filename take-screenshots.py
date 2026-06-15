#!/usr/bin/env python3
"""
Take real screenshots of Kayumanies app running at 192.168.35.50:7777/kayumanies
Uses Playwright to capture actual browser screenshots
"""

import asyncio
from playwright.async_api import async_playwright
import os

URL = "http://192.168.35.50:7777/kayumanies/"
OUT = "assets/images"

async def take_screenshots():
    os.makedirs(OUT, exist_ok=True)

    async with async_playwright() as p:
        # --- DESKTOP SCREENSHOT (1280x720) ---
        print("Opening desktop browser...")
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={"width": 1280, "height": 720},
            device_scale_factor=1,
        )
        page = await context.new_page()
        await page.goto(URL, wait_until="networkidle", timeout=30000)
        await page.wait_for_timeout(2000)  # Wait for images to load

        desktop_path = os.path.join(OUT, "screenshot-desktop.png")
        await page.screenshot(path=desktop_path, full_page=False)
        print(f"  [OK] Saved: {desktop_path}")
        await browser.close()

        # --- MOBILE SCREENSHOT (390x844 - iPhone 14 size) ---
        print("Opening mobile browser...")
        mobile_browser = await p.chromium.launch(headless=True)
        mobile_context = await mobile_browser.new_context(
            viewport={"width": 390, "height": 844},
            device_scale_factor=3,
            user_agent="Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1",
        )
        mobile_page = await mobile_context.new_page()
        await mobile_page.goto(URL, wait_until="networkidle", timeout=30000)
        await mobile_page.wait_for_timeout(2000)

        mobile_path = os.path.join(OUT, "screenshot-mobile.png")
        await mobile_page.screenshot(path=mobile_path, full_page=True)
        print(f"  [OK] Saved: {mobile_path}")
        await mobile_browser.close()

    print("\n[DONE] Real screenshots captured successfully!")
    print(f"   Desktop: {desktop_path}")
    print(f"   Mobile:  {mobile_path}")


if __name__ == "__main__":
    asyncio.run(take_screenshots())
