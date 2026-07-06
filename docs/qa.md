# QA test plan — ChefSolver Embeds

Manual test matrix for a WordPress install (block + classic).

## Setup
1. Upload `chefsolver-embeds.zip` via Plugins → Add New → Upload Plugin.
2. Activate. Confirm no PHP notices/warnings (enable `WP_DEBUG`).
3. Visit Settings → ChefSolver Embeds; click **Refresh manifest**. Expect "Manifest cached: N embeds available."

## Shortcode (Classic Editor / any content)
| # | Input | Expected |
|---|-------|----------|
| 1 | `[chefsolver_embed type="converter" slug="ml-to-grams" lang="en"]` | iframe to `https://chefsolver.com/embed/en/converters/ml-to-grams/?theme=auto`, `loading="lazy"`, `width=100%`, title set. |
| 2 | `[chefsolver_converter slug="cups-to-grams" lang="it"]` | alias forces `type=converter`; Italian embed renders. |
| 3 | `[chefsolver_embed type="ingredient-converter" slug="honey/ml-to-grams" lang="en"]` | ingredient embed to `/embed/en/convert/honey/ml-to-grams/`. |
| 4 | `[chefsolver_embed slug="ml-to-grams" theme="dark" accent="#0aa" compact="1" radius="12" height="420"]` | query has `theme=dark&accent=%230aa&compact=1&radius=12`; iframe height 420. |
| 5 | `[chefsolver_embed slug="ml-to-grams" accent="red; )"]` (invalid accent) | accent dropped; iframe still renders, no broken markup. |
| 6 | `[chefsolver_embed type="converter" slug="does-not-exist" lang="en"]` | fallback URL built for known type; host still chefsolver.com; renders (embed page itself handles unknown gracefully). |
| 7 | `[chefsolver_embed type="bogus/type" slug="x"]` (invalid type) | invalid type → HTML comment, no iframe. |
| 8 | `[chefsolver_embed slug="../../etc/passwd"]` | slug sanitized (`..` rejected) → no iframe / no traversal. |

## Block editor (Gutenberg)
- [ ] Insert "ChefSolver Embed" block.
- [ ] Type filter lists manifest types; language selector lists configured langs.
- [ ] "Search tools" filters results; selecting one sets slug/type/lang and shows a live server-side-rendered preview.
- [ ] Theme, accent, compact, radius, height controls update the preview.
- [ ] Save; view front end — same iframe as the preview (server-side rendered).

## Manifest offline / fallback
- [ ] Block editor with manifest unavailable (e.g. block outbound host or empty cache): a warning Notice appears; entering `type`, `slug`, `lang` manually still renders a validated iframe.
- [ ] Shortcode with manifest offline: known types still render via fallback URL restricted to chefsolver.com.

## Validation / security
- [ ] Invalid slug (spaces, `..`, protocol) → no iframe.
- [ ] Invalid type → no iframe.
- [ ] Invalid accent (non-hex) → accent ignored, iframe renders.
- [ ] height out of range → clamps to default.
- [ ] View source: iframe `src` host is always `chefsolver.com`; no link/credit rendered outside the iframe; no remote `<script>` from the plugin.

## Settings & capabilities
- [ ] Save settings as admin — values persist and become shortcode/block defaults.
- [ ] "Refresh manifest" as admin — success; cache updates.
- [ ] As a non-admin (e.g. subscriber): Settings page not accessible; `admin-post` refresh and REST endpoints denied.
- [ ] Cache TTL respected: manifest not refetched within TTL (check with a network log / add a temporary error_log in fetch_remote).

## Cache refresh
- [ ] Change TTL; save; confirm value stored.
- [ ] Refresh manifest button repopulates the transient.

## Uninstall
- [ ] Deactivate + Delete the plugin.
- [ ] Confirm option `chefsolver_embeds_options` and transient `chefsolver_embeds_manifest` are removed (check `wp_options`).
- [ ] No orphan tables, files, or scheduled events remain.
