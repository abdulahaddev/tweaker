# Tweaker Translations

This directory contains translation files for the Tweaker plugin.

## Text Domain
- **Text Domain**: `tweaker`
- **Domain Path**: `/languages`

## Translation Files

Translation files should be named according to WordPress standards:
- `tweaker-{locale}.po` - Portable Object file (human-readable)
- `tweaker-{locale}.mo` - Machine Object file (compiled)

### Example Locales
- `tweaker-en_US.po` / `tweaker-en_US.mo` - English (United States)
- `tweaker-bn_BD.po` / `tweaker-bn_BD.mo` - Bengali (Bangladesh)
- `tweaker-es_ES.po` / `tweaker-es_ES.mo` - Spanish (Spain)

## Creating Translations

1. **Generate POT template** (optional):
   ```bash
   wp i18n make-pot . languages/tweaker.pot --domain=tweaker
   ```

2. **Create language-specific PO file**:
   - Copy `tweaker.pot` to `tweaker-{locale}.po`
   - Translate strings in the PO file

3. **Compile MO file**:
   ```bash
   msgfmt -o tweaker-{locale}.mo tweaker-{locale}.po
   ```

## WordPress Translation

For WordPress.org hosted plugins, translations can be managed through the WordPress [GlotPress](https://translate.wordpress.org/) platform.

## Notes

All translatable strings in the plugin use:
- `__()` - Returns translated string
- `_e()` - Echoes translated string  
- `esc_html__()` - Returns translated string, escaped for HTML
- `esc_html_e()` - Echoes translated string, escaped for HTML
- `sprintf()` with translators comments for placeholders
