# Molk Film (ملك فيلم) — WordPress Academy Site

A complete, production-ready Arabic-first (RTL) film-academy site built on
**WordPress** + **Tutor LMS** + **WooCommerce** + a custom child theme + a
zero-click-config plugin.

---

## File Layout

```
wp-content/
├── themes/
│   └── molkfilm-child/          Child theme of Astra
│       ├── style.css            Theme header + global brand styles
│       ├── functions.php        Enqueue, fonts, RTL, hooks
│       ├── rtl.css              RTL-specific overrides
│       ├── assets/
│       │   ├── css/brand.css    CSS custom-properties design system
│       │   ├── js/main.js       UI JS (lang toggle, seats bar, countdown)
│       │   └── img/waves-bg.svg Wavy hero background
│       └── templates/
│           ├── archive-courses.php  Course listing (hero + filter + card grid)
│           └── single-courses.php   Course detail (stats + two-column layout)
└── plugins/
    └── molkfilm-core/           Must-use-style plugin
        ├── molkfilm-core.php    Plugin header + bootstrap
        ├── includes/
        │   ├── class-setup.php          Activation: categories, pages, sample course
        │   ├── class-course-fields.php  Custom meta (mode, seats, schedule…)
        │   ├── class-seo.php            Meta, OG, JSON-LD, GA4, sitemap
        │   ├── class-payments.php       Paymob, PayTabs, PayPal gateways
        │   └── class-admin-dashboard.php Shopify-style admin panel
        ├── assets/admin.css     Admin brand stylesheet
        └── languages/molkfilm.pot  Translation template
```

---

## Required Plugins (install before activating molkfilm-core)

| Plugin | Source | Notes |
|---|---|---|
| **Astra** theme | WordPress.org | Parent theme for the child theme |
| **Tutor LMS** (free) | WordPress.org | Course / lesson management |
| **WooCommerce** | WordPress.org | Checkout & payment processing |
| **WooCommerce PayPal Payments** | WordPress.org | PayPal gateway (official) |
| **Polylang** *(optional)* | WordPress.org | Arabic ↔ English language toggle |

> Rank Math or Yoast SEO can optionally be installed — molkfilm-core detects
> them and disables its own SEO output to avoid duplicate tags.

---

## Activation Steps

### Local (wp-env / LocalWP / DDEV)

```bash
# 1. Copy theme and plugin into your WordPress install
cp -r wp-content/themes/molkfilm-child  /path/to/wp/wp-content/themes/
cp -r wp-content/plugins/molkfilm-core  /path/to/wp/wp-content/plugins/

# 2. Install required plugins via WP-CLI (if available)
wp plugin install astra tutor-lms woocommerce woocommerce-paypal-payments --activate

# 3. Activate the child theme
wp theme activate molkfilm-child

# 4. Activate molkfilm-core (triggers activation hook → seeds data)
wp plugin activate molkfilm-core

# 5. Flush rewrite rules
wp rewrite flush
```

### Manual (via wp-admin)

1. **Plugins → Add New** → search and install: *Tutor LMS*, *WooCommerce*,
   *WooCommerce PayPal Payments*.
2. **Appearance → Themes → Add New** → upload `molkfilm-child.zip`
   (zip the theme folder first) OR install **Astra** and then upload the
   child theme folder manually via FTP/SFTP.
3. **Activate** Astra first, then activate **Molk Film Child**.
4. Upload the `molkfilm-core` plugin folder to `wp-content/plugins/` and
   **activate** it from **Plugins → Installed Plugins**.
5. Visit **ملك فيلم → نظرة عامة** in wp-admin to verify.

---

## Where to Paste Payment Credentials

All credentials are entered in **wp-admin → ملك فيلم → الإعدادات**.
Never hardcode them in files.

### Paymob (Egypt)

| Setting | Where to find it |
|---|---|
| **Paymob API Key** | Paymob Dashboard → Settings → API Keys |
| **Paymob Integration ID** | Paymob Dashboard → Developers → Integrations |
| **Paymob iFrame ID** | Paymob Dashboard → Developers → iFrames |
| **HMAC Secret** *(optional)* | Paymob Dashboard → Settings → Security |

Callback/webhook URL to register in Paymob:
```
https://yoursite.com/?wc-api=molkfilm_paymob
```

### PayTabs (Saudi Arabia / UAE)

| Setting | Where to find it |
|---|---|
| **Profile ID** | PayTabs Dashboard → Profile |
| **Server Key** | PayTabs Dashboard → Developers → Keys |

IPN callback URL to register in PayTabs:
```
https://yoursite.com/?wc-api=molkfilm_paytabs
```

### PayPal

| Setting | Where to find it |
|---|---|
| **Client ID** | developer.paypal.com → My Apps → Live credentials |
| **Secret** | developer.paypal.com → My Apps → Live credentials |
| **Mode** | Set to **Live** when ready for production |

---

## Switching to Live Payments

1. Enter live keys in **wp-admin → ملك فيلم → الإعدادات**.
2. Change **PayPal Mode** from `Sandbox` → `Live`.
3. In WooCommerce → Settings → Payments enable the gateways you want to
   expose (Paymob, PayTabs, PayPal).
4. Confirm `woocommerce_force_ssl_checkout` is `yes` — set it in wp-admin or
   via WP-CLI:
   ```bash
   wp option update woocommerce_force_ssl_checkout yes
   ```
5. Run a **test enrollment end-to-end**:
   - Add a test course with a low price.
   - Go through checkout with each gateway.
   - Verify the student appears in **ملك فيلم → الطلاب** and in
     **Tutor LMS → Enrolled Courses**.

---

## Local → Live Deployment

### Option A — Git + SFTP

```bash
git push origin main           # push to your remote
# On the server, pull wp-content only:
ssh user@yourhost
cd /var/www/html/wp-content
git pull origin main
wp plugin activate molkfilm-core
wp rewrite flush
```

### Option B — Managed Hosting (Kinsta / SiteGround / Cloudways)

1. Upload `wp-content/themes/molkfilm-child/` and
   `wp-content/plugins/molkfilm-core/` via the host's file manager or SFTP.
2. Activate plugins & theme from wp-admin.
3. Set the custom domain in the host panel.
4. Install SSL (Let's Encrypt — free on all major hosts).
5. Enter live payment keys in Settings.

---

## Customisation Reference

### Brand Colours
Edit `wp-content/themes/molkfilm-child/assets/css/brand.css` — all colours
are CSS custom properties at `:root`.

### Hero Text
**wp-admin → ملك فيلم → الإعدادات** — edit "عنوان الهيرو" and "وصف الهيرو".

### Course Custom Fields
All 11 fields (mode, seats, schedule, instructor, etc.) are edited in the
standard WordPress post editor under the **تفاصيل الدورة** meta box.

### Translations
Run `wp i18n make-pot wp-content/plugins/molkfilm-core .po` or edit
`languages/molkfilm.pot` with Poedit to produce `ar_EG.po` / `ar_EG.mo`.

---

## SEO Checklist

- [ ] Add Google Analytics ID in **ملك فيلم → مدير SEO**
- [ ] Enable Sitemap (default: on); submit URL to Google Search Console:
      `https://yoursite.com/molkfilm-sitemap.xml`
- [ ] Add per-course SEO title / description via `_mf_seo_title` /
      `_mf_seo_description` post meta (or install Rank Math for a UI)
- [ ] Verify JSON-LD Course schema with
      [Rich Results Test](https://search.google.com/test/rich-results)

---

## Security Notes

- All admin form submissions are protected with `wp_nonce_field`.
- All output is escaped with `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`.
- API keys are stored in `wp_options` — secure with a security plugin
  (Wordfence / iThemes) and ensure the database is not publicly accessible.
- Use HTTPS in production (`woocommerce_force_ssl_checkout = yes`).
- Payment webhook signatures (Paymob HMAC, PayTabs SHA-256) are verified
  before processing any order status changes.

---

*Built by Claude Code for Molk Film — all code follows WordPress Coding
Standards and GPL-2.0-or-later licensing.*
