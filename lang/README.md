# Adding a new language to csuite-crm

1. Copy `lang/en.php` to `lang/xx.php` where `xx` is the ISO 639-1 language code (e.g. `fr` for French, `de` for German).
2. Translate every value. Do not change the keys.
3. Add your language to the `$allowed` array in `api/lang.php`.
4. Add a button to the language switcher in `partials/nav.php`.
5. Submit a pull request with the title: `i18n: add [Language] translation`.

Keep translations natural — avoid literal machine translation. Business context matters.
