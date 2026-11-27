# ISM URL Redirector

WordPress/WooCommerce plugin that lets you override any site URL and **force-redirect it before WordPress, WooCommerce, or any other plugin gets to handle the request**. Typical use case: a payment widget, event platform, or any third-party service changes a link, and you need an old URL on your site to immediately send visitors to the new address — without touching theme code or other plugins.

The plugin ships with a Bootstrap-powered admin UI and keeps its configuration as a plain CSV file inside the plugin folder, so rules can also be edited/deployed with any text editor, `scp`, or `git`.

## Features

- **Early Force Redirect:** redirections fire on the `init` hook at priority 1 — before WooCommerce routing, theme templates, or any plugin that would normally answer the same URL.
- **CSV Configuration in the Plugin Folder:** one item per line — `enabled,path,target` — stored in `data/redirects.csv`. Easy to version, diff, bulk-edit, or generate externally.
- **Bootstrap Admin UI:** standalone admin page (no conflicts with WP admin styles) built on embedded Bootstrap 5.3.3 — no CDN dependency.
- **Add / Edit / Remove / Toggle:** manage items with a `+` button, an edit modal, delete with confirmation, and an on/off switch per item.
- **Automatic Full-URL Display:** each item shows the override URL with the **current site domain** — e.g. `https://example.com/?p=1` on one host and `https://example.org/?p=1` on another.
- **Domain Mismatch Warning:** when saving an item whose URL carries a foreign domain, the admin gets a warning (JS confirmation before submit and a dismissible banner after save). Matching is done by URL path, so the redirect works regardless of domain.
- **Safe by Default:** nonces and `manage_options` checks on every action, CSV writes are `flock`-protected, and the `data/` folder is blocked from direct web access.

## Requirements

- **WordPress 5.8+**
- **PHP 7.4+**
- **WooCommerce** (optional — the redirect works with or without it)
- **Bootstrap 5.3.3** — already embedded in `assets/`, no external download needed

## Installation

### From a ZIP (wp-admin)

1. Zip the plugin folder:
   ```bash
   zip -r ism-url-redirector.zip ism-url-redirector -x "*/data/redirects.csv"
   ```
2. In wp-admin go to **Plugins → Add New → Upload Plugin**, choose the ZIP, and press **Activate**.

### Manual (FTP/SSH)

```bash
# From your WordPress root
cd wp-content/plugins/

# Copy the plugin folder (git clone, scp, or unzip)
git clone https://github.com/ismdevteam/ism-url-redirector.git
# or
curl -L -o /tmp/ism-url-redirector.zip https://github.com/ismdevteam/ism-url-redirector/archive/refs/heads/main.zip
unzip -q /tmp/ism-url-redirector.zip && mv ism-url-redirector-main ism-url-redirector

# Make sure the data folder is writable by PHP
chmod 755 ism-url-redirector/data
```

Then activate **ISM URL Redirector** in wp-admin → Plugins.

## Usage

1. In wp-admin open **URL Redirector** in the left menu.
2. Press **+ Add item** and fill in:
   - **URL to override** — a path (`/?p=1`) or a full URL (`https://example.com/?p=1`). Full URLs are automatically reduced to their path for matching.
   - **Where to force-redirect** — the target: a full URL (`https://example.com`) or a site path (`/wp-admin/`).
3. Press **Save**. The rule is active immediately.
4. Use the **switch** in the list to temporarily disable a rule without deleting it, **Edit** to modify, **Delete** to remove.

### CSV format

`data/redirects.csv` — one item per line:

```
1,/?p=1,https://example.com/landing
0,/wp-admin/,https://example.com/docs
```

- Column 1: `1` = enabled, `0` = disabled (mirrors the on/off switch).
- Column 2: path (or full URL) to override.
- Column 3: force-redirect target.

> ⚠️ If you edit the CSV by hand, keep one item per line and don't add a header row. The admin UI rewrites the file in this exact format.

### Redirect behavior

- Matching is **case-insensitive** and ignores trailing slashes and query strings (`/?P=1`, `/?p=1&preview=true` both match `/?p=1`).
- The redirect is a **302 Found** and terminates the request immediately — WooCommerce, the theme, and other plugins never see it.
- Admin area (`/wp-admin/*`), AJAX, cron, and XML-RPC requests are never intercepted.

## Troubleshooting

### The redirect doesn't happen
- Check the item's **switch is on** (enabled = `1` in the CSV).
- Check for typos in the path — matching is by path; a missing leading slash is tolerated.
- If a caching plugin (WP Super Cache, etc.) serves a cached copy of the old page, clear its cache.
- Remember query strings and trailing slashes don't matter — but the path itself must match.

### "Cannot write redirects.csv" / changes not saving
- The `data/` folder must be writable by the PHP-FPM/web-server user:
  ```bash
  chmod 755 wp-content/plugins/ism-url-redirector/data
  chown <php-user>:<php-group> wp-content/plugins/ism-url-redirector/data
  ```

### Warning about a foreign domain when saving
That's the built-in safeguard. The item is still saved; redirects match by path, so the rule works. If the domain was a mistake — fix the URL and save again.

### Admin page looks unstyled
Bootstrap is loaded from `assets/css/bootstrap.min.css` and `assets/js/bootstrap.bundle.min.js` inside the plugin. Make sure both files exist (e.g. a ZIP built without them). No internet connection is required at runtime.

## Contributing

Feel free to open issues or submit pull requests to improve this plugin! Contributions are always welcome.

## License

This project is licensed under the **GPLv3** License - see the LICENSE file for details.

## Acknowledgments

- **Bootstrap** — embedded admin UI components ([getbootstrap.com](https://getbootstrap.com)), v5.3.3, MIT License. The minified `assets/css/bootstrap.min.css` and `assets/js/bootstrap.bundle.min.js` files retain their original copyright and license notices as required by the MIT License.
- **WordPress & WooCommerce** — the open-source platforms this plugin extends.
