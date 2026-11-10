# GemBooking Simple Booking Form — Architecture (v3, draft)

UI source of truth: Claude Design project "GemBooking Gutenberg Block" → `GemBooking Form.dc.html`.
Code-style source of truth: the `gembooking` plugin (autoload, class calling, file layout, build tooling).
We copy its **conventions**, not its code, and have **zero runtime dependency** on it.

Items marked **[OPEN]** are not decided (see §14). Items marked **[ASSUMED]** are my reading of an answer; correct me.

## 1. Decisions (final)

| # | Decision |
|---|---|
| 1 | Standalone plugin. Does not require, call or hook into `gembooking`. |
| 2 | Namespace `GembookingSimpleBookingForm`. Slug / text domain `gembooking-simple-booking-form`. Short prefix `gsbf` **[ASSUMED from "dev_gsbf"]**. |
| 3 | No Google Calendar, no ICS attachment. |
| 4 | Bookings are stored in one custom table (name, email, phone **optional**, slot). Purpose: stop double booking + keep data. **No admin UI** for bookings; admin sees them in the notification email. |
| 5 | Class loading / bootstrap / folder layout follow gembooking (§3, §4). |
| 6 | Availability/config lives only in block attributes. Free, wp.org compatible, PHP 8.1+, WP 6.4+. |
| 7 | Email settings are **site-wide** (one option). |
| 8 | **Decline deletes the booking row** (slot is freed immediately). |
| 9 | A `pending` booking nobody decides is **auto-deleted after 48 h** (slot freed). |
| 10 | **No `uninstall.php`.** Deleting the plugin removes nothing (table and option stay). |
| 11 | Font: WordPress's default system font stack. No webfont, no Google Fonts, no theme font loading. |
| 12 | Customer "View booking" / "Reschedule" are real features (§8). Reschedule updates the DB row. |
| 13 | `build-tools/` release pipeline is included (adapted from gembooking). |
| 14 | Once a slot's time is over it is free again (never bookable in the past; next occurrence is a new free slot). **Rows are kept, never auto-deleted** for this. |
| 15 | Reschedule/View/Cancel URLs carry only `booking id + signed token`; everything else is read from the DB. |
| 16 | On reschedule, name / email / phone are **read-only**; only the slot can change. |
| 23 | In manual mode the customer is emailed **at once** that the request was received and is **pending** ("We received your booking request"); the confirmation mail still follows when the admin confirms. |
| 17 | Admin email: Confirm / Decline only ("More options" removed). Customer email: View booking / Reschedule / **Cancel**. |
| 19 | **Reschedule needs admin approval** (manual mode): the booking goes back to `pending`. In auto mode it stays `confirmed`. |
| 21 | **Admin recipient + approval mode** (not in the design): `adminTo` defaults to the WordPress `admin_email`, approval defaults to `manual`. Two controls ("Send admin emails to", "Approval: Admin confirms by email / Confirm automatically") are added to the email modal's **Settings** tab in the design's existing style. |
| 22 | **Screens missing from the design** (reschedule warning, reschedule/view entry states, confirm/decline/cancel landing page, "time just taken" error) are built by me from the design's existing tokens and components. |
| 20 | **Reschedule releases the old slot immediately** (no hold). If the admin declines the reschedule, or it expires (48 h), the **whole booking is deleted**; the customer is warned about this before rescheduling and told again by email. |
| 18 | When a pending request expires (48 h) the customer gets a short email stating the booking's current state. |

## 2. What it does

One Gutenberg block, **GemBooking Simple Booking Form**. Visitor picks date → time → enters name, email, phone(optional).
The server validates, **stores the row**, **sends emails**. The admin confirms/declines from the email. The
customer can view or reschedule from their email. Nothing else is user-facing.

## 3. Bootstrap & autoload (gembooking pattern)

`gembooking-simple-booking-form.php`:

```
PHP-version guard (>= 8.1) → admin notice + return
final class GembookingSimpleBookingForm {                     // global namespace, like `final class GemBooking`
    private __construct(): define_constants(); load_dependency();
        register_activation_hook(activate) / register_deactivation_hook(deactivate);
        add_action('plugins_loaded', on_plugins_loaded);
        add_action('gsbf_loaded', init_plugin);
    static init():        static $instance singleton
    on_plugins_loaded():  do_action('gsbf_loaded')
    init_plugin():        do_action('gsbf_before_init'); dispatch_hooks(); do_action('gsbf_init');
    dispatch_hooks():     init:0 load_textdomain; Database::init(); Blocks::init(); Assets::init(); Api::init();
                          Frontend::init(); Privacy::init(); Classes\CleanupExpiredBookings::init();
    load_dependency():    require includes/autoload.php;
    activate():           Installer::init()   // seed settings, Database::create_initial_custom_table()
    deactivate():         Classes\CleanupExpiredBookings::unschedule()
}
```

`includes/autoload.php`: same `Autoload` class as gembooking, namespace `GembookingSimpleBookingForm`
(`spl_autoload_register`, `add_namespace_directory()`, same filename rule):

```
GembookingSimpleBookingForm\Classes\SlotCalendar   → includes/classes/slot-calendar.php
GembookingSimpleBookingForm\API\BookingController  → includes/api/booking-controller.php
GembookingSimpleBookingForm\Frontend\ActionPage    → includes/frontend/action-page.php
GembookingSimpleBookingForm\Blocks                 → includes/blocks.php
(strip namespace → camelCase boundary becomes '-' → '_' becomes '-' → '\' becomes '/' → lowercase)
```
Modules are `public static function init(): void { $self = new self(); add_action(...); }`, wired by top-level
facade files (`blocks.php`, `api.php`, `frontend.php`, `assets.php`, `database.php`, `privacy.php`).
Composer is dev-tools only (phpcs/wpcs/phpunit). No runtime vendor, no Strauss.

Names (prefix `gsbf`): constants `GSBF_{VERSION, PLUGIN_FILE, PLUGIN_BASENAME, PLUGIN_SLUG, DB_PREFIX('gsbf'),
ROOT_DIR_PATH, INCLUDES_DIR_PATH, ASSETS_DIR_PATH, ASSETS_URI, SETTINGS_NAME('gsbf_settings')}` · REST `gsbf/v1` ·
block `gsbf/booking-form` · table `{prefix}gsbf_bookings` · options `gsbf_settings`, `gsbf_db_version` · cron
`gsbf/cleanup/expired_pending` · JS global `GsbfGlobal` · script handles `gsbf-blocks`, `gsbf-frontend` · CSS
prefix `gsbf-` · query args `gsbf_action`, `gsbf_booking`, `gsbf_token`.

Co-existence with gembooking: different namespace (case-sensitive prefix match, no clash), different constants /
options / REST / block / table names. Block category `gembooking` is registered **only if absent** (gembooking's
own dedupe check), so it works with or without gembooking.

## 4. File structure

```
gembooking-simple-booking-form/
├─ gembooking-simple-booking-form.php
├─ readme.txt
├─ build.config.json                         # steps: js, pot, zip (composer/strauss off: no runtime vendor)
├─ composer.json  phpcs.xml  .distignore  .distignore-wporg  .gitignore
├─ package.json  webpack.config.js  jsconfig.json  .prettierrc.js  .prettierignore
├─ ARCHITECTURE.md  docs/
├─ build-tools/{build.mjs, README.md}        # adapted from gembooking; no vendor-licenses/strauss/i18n-strings
├─ includes/
│  ├─ autoload.php  helper.php
│  ├─ installer.php   database.php   privacy.php   assets.php   blocks.php   api.php   frontend.php
│  ├─ database/create-bookings-table.php     # CreateBookingsTable::up($prefix,$charset) via dbDelta
│  ├─ privacy/{exporter.php, eraser.php}
│  ├─ api/{availability-controller.php, booking-controller.php, email-settings-controller.php}
│  ├─ classes/
│  │   ├─ config.php  config-signer.php        # sanitize block attrs / HMAC sign+verify
│  │   ├─ slot-calendar.php                    # pure: config + date → slots
│  │   ├─ booking-repository.php               # ALL wpdb access
│  │   ├─ booking-service.php                  # create / confirm / decline / reschedule, status rules
│  │   ├─ booking-token.php                    # HMAC token (id|action|exp), like gembooking's
│  │   ├─ booking-action-url.php               # builds confirm/decline/view/reschedule URLs
│  │   ├─ cleanup-expired-bookings.php         # wp-cron: delete pending > 48h (mirrors gembooking's cleanup class)
│  │   ├─ spam-guard.php
│  │   ├─ mailer.php  mail-template.php  mail-defaults.php
│  ├─ frontend/action-page.php                # confirm/decline/cancel landing page
│  └─ render/form.php                         # Render\Form: block mount HTML
├─ blocks/booking-form/block.json
├─ templates/{form.php, action-page.php, email/{layout.php, plain.php}}
├─ dev_gsbf/                                  # JS source (gembooking: dev_gembooking)
│  ├─ blocks.js  frontend.js                  # entries
│  ├─ blocks/booking-form/{index.js, edit.js, toolbar.js, inspector/*, email-settings/*}
│  ├─ components/{BookingWidget, Calendar, SlotList, Details, Done, ...}
│  ├─ hooks/  utils/{time.js, validate.js, api.js, url-params.js}  styles/
├─ assets/build/                              # [name].[version].js (committed separately from the source)
├─ languages/   tests/{php/{bootstrap,run,integration,*-test}.php, js/*.test.js}
├─ eslint.config.cjs
```
`webpack.config.js`: spread `@wordpress/scripts` config (as gembooking), entries `blocks` + `frontend` from
`dev_gsbf`, `output.filename: [name].${version}.js` → `assets/build`, `CleanWebpackPlugin`, aliases
`@Components @Utils @Hooks`. PHP enqueues the versioned names. No tailwind, no redux.
`package.json` scripts mirror gembooking (`build`, `start`, `dist`, `dist:zip`, `dist:wporg`, `wp:pot`, lint, tests).

## 5. Design → code map

| Design element | Where |
|---|---|
| Inserter entry, toolbar (Preview form / Reset / Remove) | `block.json`, `edit.js`, `toolbar.js` |
| Sidebar Booking · Availability · Form and messages · Style | block attributes + `InspectorControls` |
| Sidebar "Email notifications" → "Configure email" modal (Settings tab editable: name, address, content type, logo, brand colour, footer; Admin / Customer email tabs are read-only previews; "Send test email") | `blocks/booking-form/email-settings/*` → `email-settings-controller` (site-wide option) |
| Calendar, slot list, 12h/24h toggle, details form, done screen, step / single layouts | shared `BookingWidget` (editor preview + front end) |
| Pending vs Confirmed done screen | server `status` |
| Admin email (Confirm / Decline) | `templates/email/admin.php` |
| Customer email (View booking / Reschedule) | `templates/email/customer.php` |

Removed from the design: Google Calendar (state, done-screen link, `gcal` key). Replaced mock with real: fake
`booked()` → rows in the table; fake `GB-####` → stored `ref`; `setTimeout` save → real request.
Font: design's Inter replaced by the WP default stack
(`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif`).

## 6. Block attributes & config trust

`formId (uuid, regenerated if a copied block duplicates one) · title · layout step|single · duration
5–480/step 5 · days[0–6] · startHour · endHour · notice · buffer · maxAdvance · timezone (IANA id) · accent ·
phoneRequired · message`.

`Classes\Config::sanitize()` clamps every field (JS mirrors it). With no stored config, `Render\Form` prints the
sanitized config + `page_url` (the permalink of the page being rendered, server-determined) + `sig =
HMAC-SHA256(json, wp_salt('auth'))` on the mount. Every REST call sends both; `ConfigSigner::verify()` rejects
tampering, so visitors cannot book outside configured hours. Works in posts, templates, patterns, widgets.

## 7. Data model

`{$wpdb->prefix}gsbf_bookings`, `dbDelta` via `Database\CreateBookingsTable::up()` (no SQL comments inside the
CREATE statement, schema version in `gsbf_db_version`).

| Column | Type | Note |
|---|---|---|
| `id` | BIGINT UNSIGNED AI | PK |
| `ref` | VARCHAR(16) UNIQUE | public code `GB-7K3M9Q`, not sequential |
| `form_id` | CHAR(36) | block `formId` |
| `page_url` | TEXT | page where it was booked (from the signed config); used to build the email links |
| `accent` | VARCHAR(7) | the booked form's accent colour (brand colour of later emails) |
| `event_title` | VARCHAR(255) | snapshot |
| `start_utc`, `end_utc` | DATETIME | UTC |
| `timezone` | VARCHAR(64) | IANA id the slot was offered in |
| `duration` | SMALLINT UNSIGNED | minutes |
| `name` | VARCHAR(190) NOT NULL | |
| `email` | VARCHAR(190) NOT NULL | |
| `phone` | VARCHAR(64) **NULL** | optional |
| `status` | VARCHAR(16) | `pending` · `confirmed` |
| `slot_key` | CHAR(64) **UNIQUE** | `sha256(form_id\|start_utc)` |
| `ip_hash` | CHAR(64) NULL | salted SHA-256, spam control |
| `requested_at` | DATETIME | when the current `pending` request started: set on create **and on every reschedule**; drives the 48 h expiry |
| `rescheduled_at` | DATETIME **NULL** | set when a reschedule is requested, cleared when the admin confirms; used only to pick the right email wording (declined / expired *reschedule* vs a new request) |
| `created_at`, `updated_at` | DATETIME | |

Indexes: `uq_ref`, `uq_slot`, `idx_form_time (form_id, start_utc)`, `idx_email`, `idx_status_requested (status, requested_at)`.

**Double-booking guard:** the INSERT is the lock — `slot_key` UNIQUE; duplicate-key → HTTP 409 "that time was just
taken". Since decline / expiry **delete** the row, no NULL-key trick is needed. `idx_form_time` also lets
availability exclude any slot that *overlaps* a held row (covers later edits to duration/buffer). "Taken" is
scoped per `formId`.

**Slot release (confirmed):** a slot is a concrete date-time. When its time has passed it can no longer be booked
(notice/`maxAdvance` rules), and the same clock time next week is a different slot that is free by itself. No
job is needed for this, and past rows are **kept** (not deleted).

## 8. Flows

**Slots** — `GET /availability?month=|date=` → `SlotCalendar` (weekday ∈ days; `notice ≤ d ≤ maxAdvance` in the
form timezone; slots from `startHour`, step `duration+buffer`, `slot+duration ≤ endHour`; DST via
`DateTimeImmutable`), then rows with `pending|confirmed` in that range mark slots unavailable (greyed out).

**Create** — `POST /bookings {config,sig,start,name,email,phone?,hp,t}` → verify sig → sanitize → `SpamGuard`
(honeypot, min fill time, rate limit) → `start` must be a free real slot → INSERT (409 on duplicate) →
`manual`: status `pending`, email **admin** (details + Confirm/Decline) and email the **customer** a "We received
your booking request" mail that says the booking is still pending (with View / Reschedule / Cancel; the host's
confirmation follows in a second mail) · `auto`: status `confirmed`, email
**customer** (confirmation with View/Reschedule) + FYI to **admin** → `201 {ref,status,start,end}`.

**Confirm / Decline (admin, manual mode)** — email link `{page}?gsbf_action=confirm|decline&gsbf_booking=<id>&gsbf_token=<t>`
→ landing page (GET never changes state, scanner-safe) → the button POSTs to the same URL (handled by
`Frontend\ActionPage`, no REST route) → token valid + row is
`pending` → confirm: status `confirmed` + customer email (View/Reschedule) · decline: customer "declined" email,
then **DELETE row**. Second click → "already decided", no email.

**Expiry** — `Classes\CleanupExpiredBookings` (wp-cron hourly; only the *cron shape* is copied from gembooking's
cleanup class: `init()` + `ensure_scheduled` + `run()` + `unschedule()` on deactivate). It handles only rows with
`status='pending' AND requested_at < now − 48h` (age counted from when the customer's current request was made, not the slot date),
in batches of 500: for each row **first** send the customer a short "request expired" email, **then** DELETE the
row (a failed `wp_mail` never blocks the delete, so the slot is always freed). `confirmed` rows are never touched. A `pending` row with `rescheduled_at` set (an unapproved reschedule)
is deleted by the same rule and gets the reschedule wording: "your reschedule was not approved in time, your
booking has been cancelled and both times are released, you can book again".
The expiry email says plainly: the request was not confirmed in time, the time slot is released, and links to the
booking page to choose a time again.

**View booking (customer)** — email link `{page_url}?gsbf_action=view&gsbf_booking=<id>&gsbf_token=<t>`. The mount
whose `formId` matches calls `GET /bookings/{id}?token=` and opens the widget directly on the **done / success
screen** for that booking (Pending or Confirmed, with all details).

**Reschedule (customer)** — email link `{page_url}?gsbf_action=reschedule&gsbf_booking=<id>&gsbf_token=<t>` → the
form page opens; the widget fetches the booking via `GET /bookings/{id}?token=`, jumps to the booked month,
shows the **current date + slot pre-selected**; name / email / phone are shown **read-only**. Customer picks a new slot → `POST
/bookings/{id}/reschedule {token,config,sig,start}` → token (`reschedule`) valid, row not in the past, new slot
free per `SlotCalendar` → `UPDATE start_utc,end_utc,slot_key` (UNIQUE → 409 if taken). Then by approval mode:
`manual`: status → `pending`, `requested_at = now`, email **admin** ("reschedule request" with fresh Confirm /
Decline links) + **customer** (short "reschedule received, waiting for approval"); `auto`: status stays
`confirmed`, email **admin** (FYI) + **customer** (updated confirmation, fresh links).
The URL carries only `booking id + signed token`; date/slot/details are fetched from the DB.
**Old slot is released at once** (the UPDATE replaces `slot_key`, so anyone can book the old time right away);
there is no hold and no extra columns. In `manual` mode `rescheduled_at = now`. If the admin **Declines** the
reschedule, or 48 h pass without a decision, the row is **deleted** (customer gets the reschedule-wording email:
reschedule not approved, booking cancelled, you can book again). Admin **Confirm** → status `confirmed`,
`rescheduled_at = NULL`, customer email with fresh View / Reschedule / Cancel links. Because of this, the
reschedule screen shows the customer a warning first (manual mode only): *"Your current time will be released
right away. If the admin does not approve the new time within 48 hours, your booking will be cancelled."* The
customer must tick/confirm before submitting.

**Cancel (customer)** — email link `{page_url}?gsbf_action=cancel&gsbf_booking=<id>&gsbf_token=<t>` → landing page
(`Frontend\ActionPage`, GET shows booking summary + "Cancel booking" button, POST executes, scanner-safe) →
the POST → token valid + row exists → emails **customer** (cancelled confirmation) + **admin**
(booking cancelled), then **DELETE row** (slot freed, same as decline) **[ASSUMED: delete, like decline]**.

Tokens: `base64url(exp . '.' . HMAC(id|action|exp, wp_salt('auth')))` (same construction as gembooking's
`BookingToken`, re-implemented here), actions `confirm`, `decline`, `view`, `reschedule`, `cancel`; constant-time
compare. Expiry: confirm/decline `requested_at + 48h`; view `end_utc`; reschedule/cancel `start_utc` **[ASSUMED]**.

## 9. Email

**Re-checked against the updated design.** The email modal now has three tabs: **Settings** (editable) and
**Admin email / Customer email** (read-only previews: the old right-hand "Field settings" panel that let you edit
every label/text/placeholder was removed). So **email texts are fixed built-in defaults in v1**
(`Classes\MailDefaults`), not stored, not editable, no user-facing placeholders.

Site-wide option `gsbf_settings` (normal array) therefore holds only what the Settings tab edits:
`fromName · fromEmail · type html|plain · logo (attachment id) · footerHtml` + `adminTo` (default `admin_email`) and `approval
manual|auto` (default `manual`) with the two Settings-tab controls from decision 21. Brand colour in the modal is **not** stored here:
it edits the block's `accent` attribute, and each email uses the accent from the signed config of the form that
was booked (so several forms can have different colours while the other settings stay site-wide).
Only `footerHtml` allows kses (`a strong em u ul ol li br p`). `wp_mail()`, `Reply-To` = customer email on admin
mails. "Send test email" → `POST /email-settings/test` (sample data, sent to `adminTo`).

Buttons (from the updated design): **admin email** Confirm / Decline (no third button); **customer email**
View booking / Reschedule / Cancel (third button shown for the customer email only).
Mails not in the design use the same built-in defaults and each states the booking's current state in one or two
sentences: **declined**, **rescheduled** (admin + customer, with the new date/time), **cancelled** (admin +
customer), **expired** (customer). HTML emails use the same system font stack.

## 10. REST (`gsbf/v1`)

| Route | Method | Auth |
|---|---|---|
| `/availability` | GET | public + signed config |
| `/bookings` | POST | public + signed config + anti-spam |
| `/bookings/{id}` | GET | signed token (`view` or `reschedule`), returns only that booking |
| `/bookings/{id}/reschedule` | POST | signed token + signed config |
| `/email-settings` (+`/test`, `/preview`) | GET/POST | `manage_options` + REST nonce |

Explicit `permission_callback`, `args` sanitize/validate, `WP_Error` with proper codes (400/403/409/429).

## 11. Security / privacy

Signed config + signed tokens; sanitize on input, escape late; `$wpdb->prepare` everywhere; header-injection safe
mail; rate limit on salted IP hash (transients); tokens never in logs beyond the URL they live in, bookings are
fetched by id only with a valid token (no list endpoint). PII (name/email/phone) is stored → `Privacy\Exporter` +
`Privacy\Eraser` (eraser anonymises name/email/phone and keeps the slot row so future-slot protection is not
broken). Reschedule/view URLs are bearer links: anyone with the email link can act, same model as gembooking.

## 12. Frontend

Shared `BookingWidget` (editor: mock API adapter; front end: REST adapter). State machine from the design:
`step` = pick → details → done, `single` = date → time → details → done, plus `view` (opens on done) and
`reschedule` (preselected current slot, name/email/phone read-only, approval warning in manual mode) entry modes driven by `utils/url-params.js`. Tokens
as CSS variables (`#141A24 #434A51 #646C73 #E0E4E8 #F6F7F8`, radius 6/8/12, accent var). 44 px targets, aria
labels, focus-visible, mobile ≤390 px. Script only enqueued on pages that contain the block. No cookies.

## 13. Quality / release

`phpcs.xml` from gembooking (WPCS, PHPCompatibilityWP, text domain `gembooking-simple-booking-form`); PHPUnit for
`SlotCalendar`, `BookingToken`, `Config`, `ConfigSigner`, `BookingService` rules; `npm run dist` (JS build → POT → zip
from `.distignore`), `dist:wporg` uses `.distignore-wporg` (ships `dev_gsbf` source for review).

## 14. Open questions
None. Design re-read done (customer-only third button; per-field email editing panel removed). Decisions 21-22
close the two gaps found (admin recipient / approval mode controls, and the screens missing from the design).
Next step is implementation, in this order: bootstrap + autoload + constants → database + installer →
`Config`/`ConfigSigner`/`SlotCalendar` (+ unit tests) → REST + `BookingService` → email → block + widget.

## 15. Implementation status (built and verified)

Everything in sections 1-14 is implemented. Differences from the plan above:

- No `functions.php` (nothing needed it). Confirm / decline / cancel are handled by the landing page's own POST,
  so there is no `booking-actions` REST route and no `booking-action-controller.php`.
- The bookings table has an `accent` column (emails after a click on a link need the form's brand colour).
- The design's email preview shows no banner / body text / footer; the real emails add a one-line status banner and a
  short body sentence (needed so every state, e.g. "reschedule not approved", is explained), and the footer text.
- `phone` is accepted only as digits and `+ - ( ) .` and spaces.
- Release: `npm run dist` (JS build -> POT -> zip) works; the zip has no vendor, no source, no dev files.

Verification done:

| Check | Result |
|---|---|
| `php tests/php/run.php` (SlotCalendar incl. DST, Config clamping, BookingToken, ConfigSigner) | 24 passed |
| `wp eval-file tests/php/integration.php` (REST availability / create / double booking / spam / view / confirm / reschedule / decline / cancel / 48 h expiry / auto approval / privacy / previews, real DB) | 46 passed |
| `npm run test:unit` (JS time + slot rules) | 5 passed |
| `vendor/bin/phpcs` (WPCS + PHPCompatibilityWP 8.1) | clean |
| `npm run lint:js` | clean |
| Real HTTP: block renders, REST over HTTP, confirm landing page GET/POST/second click/forged token | works |
| Browser (Chrome): front end pick -> details -> pending screen, reschedule entry from email link (current slot preselected), "One at a time" layout, block editor (sidebar, email modal, admin email preview) | works, no console errors |

Not verified: a real mobile viewport (the CSS wraps with flex and `minmax`, but the window could not be narrowed in
this session), a real SMTP delivery (mail was captured with `pre_wp_mail`), WordPress.org Plugin Check, translations.
