![header.svg](assets/header.svg)

# Small Pics for Craft CMS

Add [Small Pics](https://www.smallpics.io) transforms to image URLs in Craft CMS.

## Requirements

- Craft CMS 4.5+ or 5.0+
- PHP 8.1+

## Installation

```bash
composer require smallpics/craft-smallpics
./craft plugin/install smallpics
```

## Configuration

Create `config/smallpics.php`.

### Single Origin

```php
return [
    'transformNativeImages' => true,
    'nativeTransformsParams' => [
        'q' => 55,
    ],
    'transformThumbnails' => true,
    'thumbnailParams' => [
        'q' => 50,
    ],
    'baseUrl' => getenv('SMALLPICS_BASE_URL'),
    'secret' => getenv('SMALLPICS_SECRET') ?: null,
    'transformSvgs' => false,
    'transformAnimatedGifs' => false,
    'defaultParams' => [
        'fm' => 'avif', // Don't set a default format if you've enabled `transformAnimatedGifs`
        'q' => 65,
    ],
];
```

### Multiple Origins

Use origin labels to select the source setup for an image. The label is only used by the plugin and is not added to generated URLs.

```php
return [
    'transformNativeImages' => true,
    'nativeTransformsParams' => [
        'q' => 55,
    ],
    'transformThumbnails' => true,
    'thumbnailParams' => [
        'q' => 50,
    ],
    'defaultOrigin' => 'productImages',
    'origins' => [
        'productImages' => [
            'baseUrl' => getenv('SMALLPICS_PRODUCTS_BASE_URL'),
            'secret' => getenv('SMALLPICS_PRODUCTS_SECRET') ?: null,
            'transformSvgs' => false,
            'transformAnimatedGifs' => false,
            'defaultParams' => [
                'q' => 80,
            ],
        ],
        'editorialImages' => [
            'baseUrl' => getenv('SMALLPICS_EDITORIAL_BASE_URL'),
            'secret' => getenv('SMALLPICS_EDITORIAL_SECRET') ?: null,
            'transformSvgs' => false,
            'transformAnimatedGifs' => false,
        ],
    ],
];
```

## Native Craft Transforms

Native Craft image transforms are handled automatically when `transformNativeImages` is enabled. Use `nativeTransformsParams` to set Small Pics params for native transforms only.

```twig
{{ asset.getUrl({ width: 800, height: 600, mode: 'crop' }) }}
{{ asset.getImg('hero') }}
{{ asset.getSrcset(['400w', '800w'], { width: 800 }) }}
```

Craft thumbnail URLs are handled automatically when `transformThumbnails` is enabled. Use `thumbnailParams` to override defaults for thumbnails.

```php
return [
    'transformThumbnails' => true,
    'thumbnailParams' => [
        'q' => 50,
    ],
];
```

### Native Transform Key Mapping

Craft transform keys are translated to Small Pics keys when native transforms are intercepted:

| Craft key  | Small Pics param        |
|------------|-------------------------|
| `width`    | `w`                     |
| `height`   | `h`                     |
| `quality`  | `q`                     |
| `mode`     | `fit`                   |
| `position` | cover position in `fit` |
| `fill`     | `bg`                    |

## Twig

`transformImage()` returns a `TransformedImage`. Rendering it as a string outputs the URL.

```twig
<img src="{{ craft.smallpics.transformImage(asset, {
    w: 800,
    h: 600,
    fit: 'cover',
    fm: 'webp',
    q: 80
}) }}">
```

You can also pass a named Craft transform handle as the config.

```twig
<img src="{{ craft.smallpics.transformImage(asset, 'hero') }}">
```

Select an origin with `origin`.

```twig
{{ craft.smallpics.transformImage(asset, {
    origin: 'editorialImages',
    w: 1200,
    fm: 'avif'
}) }}
```

`srcset()` takes the image, descriptors, and common config.

```twig
<img srcset="{{ craft.smallpics.srcset(
    asset, 
    {
        '1x': { dpr: 1 },
        '2x': { dpr: 2 },
        '800w': { w: 800 }
    }, 
    {
        w: 400,
        h: 300,
        fit: 'cover',
        fm: 'webp'
    }
) }}">
```

## Transform Options

Transform options can use either the Small Pics URL param key or the option name used by `smallpics/smallpics-php`. For example, `q` and `quality` are equivalent.

Internally, the plugin normalizes URL param keys before creating `smallpics\smallpics\Options`. That means `q` becomes `quality`, then the underlying package calls `setQuality()`.

The examples below use PHP array syntax. Use the equivalent object or array syntax in Twig templates.

Use a single value for setters that take one argument:

```php
[
    'w' => 800,
    'q' => 80,
    'fm' => 'webp',
]
```

Use an array for setters that take multiple arguments. Array values are spread into the underlying setter in order:

```php
[
    'crop' => [400, 300, 10, 20],
    'ar' => [16, 9],
    'border' => [8, 'ffffff', 'pad'],
    'fit' => ['cover', 'cover-top'],
]
```

| Param key | Option name | Value | Example                            | Setter |
|-----------|-------------|-------|------------------------------------|--------|
| `or` | `orientation` | `0`, `90`, `180`, `270`, or `auto` | `'or' => 'auto'`                   | `setOrientation()` |
| `flip` | `flip` | `v`, `h`, or `both` | `'flip' => 'h'`                    | `setFlip()` |
| `crop` | `crop` | `[width, height, x, y]` | `'crop' => [400, 300, 10, 20]`     | `setCrop()` |
| `w` | `width` | Integer width | `'w' => 800`                       | `setWidth()` |
| `h` | `height` | Integer height | `'h' => 600`                       | `setHeight()` |
| `ar` | `aspectRatio` | Ratio number, or `[dividend, divisor]` | `ar => 1.778` or `'ar' => [16, 9]` | `setAspectRatio()` |
| `fit` | `fit` | `contain`, `max`, `fill`, `fill-max`, `stretch`, `cover`, `crop`, or `[fit, cropPosition, focalPointX, focalPointY, zoom]` | `'fit' => ['cover', 'cover-top']`  | `setFit()` |
| `dpr` | `devicePixelRatio` | Integer device pixel ratio | `'dpr' => 2`                       | `setDevicePixelRatio()` |
| `bri` | `brightness` | Integer brightness | `'bri' => 10`                      | `setBrightness()` |
| `con` | `contrast` | Integer contrast | `'con' => 15`                      | `setContrast()` |
| `gam` | `gamma` | Float gamma | `'gam' => 1.2`                     | `setGamma()` |
| `sharp` | `sharpen` | Integer sharpen amount | `'sharp' => 20`                    | `setSharpen()` |
| `blur` | `blur` | Integer blur amount | `'blur' => 5`                      | `setBlur()` |
| `pixel` | `pixelate` | Integer pixelate amount | `'pixel' => 8`                     | `setPixelate()` |
| `filt` | `filter` | `grayscale` or `sepia` | `'filt' => 'grayscale'`            | `setFilter()` |
| `mark` | `watermarkPath` | Watermark image path | `'mark' => '/watermark.png'`       | `setWatermarkPath()` |
| `markorigin` | `watermarkOrigin` | Watermark origin name | `'markorigin' => 'default'`        | `setWatermarkOrigin()` |
| `markw` | `watermarkWidth` | Integer width or relative width string | `'markw' => 120`                   | `setWatermarkWidth()` |
| `markh` | `watermarkHeight` | Integer height or relative height string | `'markh' => 80`                    | `setWatermarkHeight()` |
| `markfit` | `watermarkFit` | Same shape as `fit` | `'markfit' => 'contain'`           | `setWatermarkFit()` |
| `markx` | `watermarkXOffset` | Integer offset or relative offset string | `'markx' => 20`                    | `setWatermarkXOffset()` |
| `marky` | `watermarkYOffset` | Integer offset or relative offset string | `'marky' => 20`                    | `setWatermarkYOffset()` |
| `markpad` | `watermarkPadding` | Integer padding or relative padding string | `'markpad' => 16`                  | `setWatermarkPadding()` |
| `markpos` | `watermarkPosition` | `top-left`, `top`, `top-right`, `left`, `center`, `right`, `bottom-left`, `bottom`, or `bottom-right` | `'markpos' => 'bottom-right'`      | `setWatermarkPosition()` |
| `markalpha` | `watermarkAlpha` | Integer alpha | `'markalpha' => 80`                | `setWatermarkAlpha()` |
| `bg` | `background` | Background color string | `'bg' => 'ffffff'`                 | `setBackground()` |
| `border` | `border` | `[width, color, method]`, where method is `overlay`, `shrink`, or `pad` | `'border' => [8, 'ffffff', 'pad']` | `setBorder()` |
| `q` | `quality` | Integer quality | `'q' => 80`                        | `setQuality()` |
| `fm` | `format` | `jpg`, `pjpg`, `png`, `gif`, `webp`, or `avif` | `'fm' => 'webp'`                   | `setFormat()` |
| `interlace` | `interlaced` | Boolean | `'interlace' => true`              | `setInterlaced()` |

Cover crop positions for `fit` and `markfit` are `cover-top-left`, `cover-top`, `cover-top-right`, `cover-left`, `cover-center`, `cover-right`, `cover-bottom-left`, `cover-bottom`, and `cover-bottom-right`.

For focal-point crops, pass `null` for the crop position argument: `'fit' => ['crop', null, 50, 50]` or `'fit' => ['crop', null, 50, 50, 2]`.

## PHP

```php
use smallpics\craft\Plugin;

$image = Plugin::$instance->transformer->transformImage(
    $asset, 
    [
        'w' => 800,
        'h' => 600,
        'fm' => 'webp',
    ]
);

$url = (string) $image;
```

```php
$srcset = Plugin::$instance->transformer->srcset(
    $asset, 
    [
        '1x' => ['dpr' => 1],
        '2x' => ['dpr' => 2],
    ], 
    [
        'w' => 400,
        'h' => 300,
        'fit' => 'cover',
    ]
);

$srcsetValue = (string) $srcset;
```
