# Release build

`build.mjs` is the same cross-platform release builder GemBooking uses (copied
unchanged). One command produces a shippable zip on Linux, macOS and Windows.

```bash
npm run dist          # full release: JS build -> POT -> zip
npm run dist:zip      # only repackage the current tree
npm run dist:wporg    # WordPress.org build: also ships the JS/Sass source (.distignore-wporg)

node build-tools/build.mjs --only=js|pot|zip
node build-tools/build.mjs --skip-pot
```

This plugin has no runtime Composer dependencies, so `build.config.json` turns the
`composer` and `strauss` steps off. Composer is only used for the dev tools
(phpcs / WPCS).

| # | Step | How |
|---|------|-----|
| 1 | Production JS | `wp-scripts build` into `assets/build/` (versioned file names) |
| 2 | POT | `npm run wp:pot` -> `languages/gembooking-simple-booking-form.pot` |
| 3 | Zip | built in Node from `.distignore`; written to the parent `plugins/` folder |

The version comes from `package.json` (webpack and the zip name read it) and
must equal `GSBF_VERSION` / the `Version:` header in the main plugin file.
