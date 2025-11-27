# PWA Icon Generation

## Quick Start - Use the HTML Generator (Easiest!)

1. **Open the icon generator in your browser:**
   - Double-click `icon-generator.html`
   - Or open it in any web browser

2. **Customize (optional):**
   - Change background color (default: blue #2196f3)
   - Change icon color (default: white #ffffff)
   - Click "Generate Icons" to preview

3. **Download:**
   - Click "Download 192x192" button
   - Click "Download 512x512" button
   - Save both files to this folder as:
     - `icon-192x192.png`
     - `icon-512x512.png`

4. **Done!** The PWA will now use your custom icons.

---

## Alternative Method - Convert SVG Files

If you prefer to use the SVG files provided:

### Using Online Converter (Easiest)
1. Go to https://cloudconvert.com/svg-to-png
2. Upload `icon-192x192.svg`
3. Convert and download
4. Repeat for `icon-512x512.svg`
5. Rename files to remove any extra suffixes

### Using Inkscape
```bash
inkscape icon-192x192.svg --export-filename=icon-192x192.png
inkscape icon-512x512.svg --export-filename=icon-512x512.png
```

### Using ImageMagick
```bash
magick icon-192x192.svg icon-192x192.png
magick icon-512x512.svg icon-512x512.png
```

---

## Customizing the Icon

### Change Colors
Edit the SVG files directly:
- `fill="#2196f3"` - Background color
- `fill="#ffffff"` - Icon color

### Change Icon
Replace the `<path d="...">` in the SVG with any Material Design icon path from:
https://pictogrammers.com/library/mdi/

Popular housekeeping icons:
- `vacuum` - Vacuum cleaner (current)
- `broom` - Broom
- `spray-bottle` - Cleaning spray
- `bed` - Bed icon
- `clipboard-check-outline` - Checklist

---

## Icon Requirements

**Specifications:**
- **192x192**: Android home screen, minimal size
- **512x512**: Splash screen, high-res displays
- **Format**: PNG with transparency
- **Purpose**: PWA installable app icons

**Design Guidelines:**
- Use safe zone (10% padding from edges)
- Ensure visibility at small sizes
- Use high contrast colors
- Match brand colors
- Test on both light and dark backgrounds

---

## Files in This Directory

- `icon-generator.html` - Browser-based icon generator (recommended)
- `icon-192x192.svg` - Source SVG for 192px icon
- `icon-512x512.svg` - Source SVG for 512px icon
- `icon-192x192.png` - *Generated PNG (download from HTML tool)*
- `icon-512x512.png` - *Generated PNG (download from HTML tool)*

---

## Troubleshooting

**Icons not showing after installation:**
1. Clear browser cache
2. Uninstall PWA from home screen
3. Reinstall PWA
4. Check file names match exactly

**Wrong colors:**
- Open HTML generator
- Adjust color pickers
- Regenerate and re-download

**Need different icon:**
- Find Material Design icon at https://fonts.google.com/icons
- Get SVG path
- Update `vacuumIconPath` in `icon-generator.html`
