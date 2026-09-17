# Changelog

## 2.0.0 - Unreleased

> {warning} This update contains breaking changes. Read the [upgrade guide](https://github.com/SmallPics/craft-smallpics/blob/main/migrating-v1-v2.md) before updating.

- Remove origin settings and `OriginConfig`; use sources
- Default `transformAnimatedGifs` to `true`
- Set `passthrough=1` when `transformSvgs` is false
- Require `smallpics/smallpics-php:^2.0.0`
- Updated to use new Small Pics params

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
