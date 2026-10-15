# Languages

The bundle follows the locale of the request (`$request->getLocale()`): its texts, the direction of the page and the
format of the numbers and dates. It is translated in French, English, German and Italian; a project that offers
another language (Arabic, for example) translates the `LleCruditUiBundle` domain in its own `translations/`.

## Direction

`ui_lang()` and `ui_dir()` give the `lang` and `dir` attributes of `<html>` (see
[Installation](installation.md#language-direction-and-theme)):

| Locale of the request | `ui_lang()` | `ui_dir()` |
|---|---|---|
| `fr` | `fr` | `ltr` |
| `ar_MA` | `ar-MA` | `rtl` |
| `he` | `he` | `rtl` |

The languages written from right to left are Arabic, Persian, Hebrew and Urdu (`ar`, `fa`, `he`, `ur`), whatever the
region; the `rtl_languages` option changes the list (see [Configuration](configuration.md)). With `dir="rtl"`, the
CSS mirrors the layout: see [Right to left](styles.md#right-to-left).

## Numbers and dates

`ui_number`, `ui_currency`, `ui_date`, `ui_datetime` and `ui_time` format in the locale of the request, with the
separators of its country, **always in Latin digits and in the Gregorian calendar**. They take the arguments of the
`format_*` filters of [twig/intl-extra](https://twig.symfony.com/doc/3.x/filters/format_number.html), without the
locale and the calendar:

```twig
{{ order.amount|ui_number }}
{{ order.amount|ui_currency('EUR') }}
{{ order.date|ui_date('short') }}
{{ order.updatedAt|ui_datetime('short', 'short') }}
```

| Locale | `1234.5\|ui_number` | `date\|ui_date('short')` |
|---|---|---|
| `fr` | `1 234,5` | `13/10/2026` |
| `en` | `1,234.5` | `10/13/26` |
| `ar_SA` | `1,234.5` | `13/10/2026` |
| `ar_MA` | `1.234,5` | `13/10/2026` |
| `fa` | `1,234.5` | `2026/10/13` |

The `format_*` filters of Twig give, for the same locales, Arabic-Indic or Persian digits (`١٬٢٣٤٫٥` in `ar_SA`,
`۲۰۲۶/۱۰/۱۳` in `fa`), in the Gregorian calendar too: use the `ui_*` filters. The Latin digits are not configurable,
on purpose; a project that wants the digits of the locale keeps the `format_*` filters. A `numbers` keyword in the
locale (`ar_SA@numbers=arab`) is replaced.

The filters need the `intl` extension of PHP, required by the bundle. They use their own instance of the extension
of twig/intl-extra: the prototypes of formatters configured by the project in `twig_extra.intl` do not apply to them.
The dates may differ slightly from one version of ICU to the other (`13/10/2026 م` in `ar_SA` since ICU 74).

The default locale of PHP is not changed: a `NumberFormatter` or an `IntlDateFormatter` created in PHP follows the
usual rules of the locale. Outside a request (command, message handler), the filters use `kernel.default_locale`.

## Writing a template

- A text goes through a translation key, never a sentence written in the template.
- No locale in a template: no `'fr'` given to a filter, no date pattern (`d/m/Y`): the format comes from the locale.
- A free text (long text, Markdown, rich HTML) gets `dir="auto"`, a phone number or a code `dir="ltr"`.
- A controller receives its texts from Twig, already translated (see [Contributing](development.md#conventions)).
