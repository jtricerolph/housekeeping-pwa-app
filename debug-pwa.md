# PWA Installation Troubleshooting

## Quick Checks

1. **Is your site on HTTPS?**
   - PWA requires HTTPS (except localhost)
   - Check if your site URL starts with `https://`

2. **Check Manifest Loading**
   - Visit: `https://yoursite.com/manifest.json`
   - Should show JSON with app name and icons

3. **Check Service Worker**
   - Visit: `https://yoursite.com/service-worker.js`
   - Should show JavaScript code

4. **Chrome DevTools Check**
   - Open Housekeeping page
   - Press F12
   - Go to Application tab
   - Check Manifest section
   - Check Service Workers section

## Common Issues

### Issue 1: Rewrite Rules Not Working

WordPress rewrite rules need to be flushed.

**Fix:**
1. Go to WordPress Admin → Settings → Permalinks
2. Click "Save Changes" (don't change anything)
3. This flushes rewrite rules

### Issue 2: .htaccess Issues

The manifest.json and service-worker.js might be blocked.

**Check:** Visit these URLs directly:
- `https://yoursite.com/manifest.json`
- `https://yoursite.com/service-worker.js`

If you get 404, the rewrite rules aren't working.

### Issue 3: Site Not on HTTPS

PWA only works on:
- HTTPS sites
- localhost (for testing)

**Check:** Look at your browser address bar. Does it show a lock icon?

### Issue 4: Browser Compatibility

**Supported:**
- Chrome/Edge on Android
- Safari on iOS (limited)
- Chrome/Edge on desktop

**Not Supported:**
- Firefox (doesn't show install prompt)
- Older browsers

## Testing Steps

1. Visit the Housekeeping page
2. Open Chrome DevTools (F12)
3. Go to Console tab
4. Look for errors
5. Go to Application tab
6. Check Manifest and Service Workers
