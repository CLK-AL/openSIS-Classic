# White-Label Branding

Place your custom branding files here to replace the default openSIS logos.

## Files

| File | Size | Where it appears |
|------|------|------------------|
| `logo.png` | ~200x50px | Navbar (top-left of every page) |
| `logo-login.png` | ~300x80px | Login page, forgot password, installer |
| `favicon.ico` | 32x32px | Browser tab icon |
| `favicon.png` | 32x32px | PNG alternative to favicon.ico |

## How it works

The system checks `assets/branding/` first. If a custom file exists, it's used
instead of the default openSIS logo. If not, the default is used.

Edit `WhiteLabelInc.php` in the project root to change:
- `app_name` — Brand name shown in titles
- `app_title` — Full title for page headers
- `footer_html` — Footer text/links on every page
