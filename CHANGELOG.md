# Changelog

## 1.3.0 - 2026-08-14

- `getMimeType()` returns `image/avif` as the default if no `fm` param has been set
- Include `jxl` in the list of types in `TransformedImage::getMimeType()`
- Require at least `smallpics/smallpics-php` ^1.2.0

## 1.2.0 - 2026-08-12

- Rename origin syntax to sources; Deprecated origin syntax

## 1.1.0 - 2026-07-24

- Default `transformThumbnails` to `true` to be consistent with `transformNativeImages`
- Fix control panel error when installing without a config file present

## 1.0.0 - 2026-07-16

- Initial release
