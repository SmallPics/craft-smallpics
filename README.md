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

or with DDEV

```bash
ddev composer require smallpics/craft-smallpics
ddev craft plugin/install smallpics
```

## Configuration

Create `config/smallpics.php`.

`baseUrl` is the only required config value.

### Configuration parameters

| Parameter | Type | Required | Default | Description                                                                                                                                      |
|-----------|------|----------|---------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| `transformNativeImages` | Boolean | No | `true` | Use Small Pics for Craft's native image transforms.                                                                                              |
| `transformThumbnails` | Boolean | No | `true` | Use Small Pics for Craft thumbnail URLs.                                                                                                         |
| `thumbnailParams` | Array | No | `[]` | Transform parameters applied to thumbnails after global and origin defaults.                                                                     |
| `nativeTransformsParams` | Array | No | `[]` | Transform parameters applied to native Craft transforms after global and origin defaults.                                                        |
| `defaultOrigin` | String | No | `'default'` | Origin to use when a transform does not specify one. If omitted, when `origins` is set, the first origin is the default.                         |
| `baseUrl` | String | Yes | _None_ | Small Pics base URL for the default single origin. Required when `origins` is empty.                                                             |
| `secret` | String or `null` | No | `null` | Signing secret for the default single origin. Required if signed requests are enabled for your image source in Small Pics.                                                                                                   |
| `transformSvgs` | Boolean | No | `false` | Transform SVGs for the default single origin.                                                                                                    |
| `transformAnimatedGifs` | Boolean | No | `true` | Transform animated GIFs for the default single origin.                                                                                           |
| `origins` | Array | No | `[]` | See [Origin configuration](#origin-configuration). |
| `defaultParams` | Array | No | `[]` | Transform parameters applied to every request before origin and per-transform parameters.                                                        |

### Origin configuration

| Parameter | Type | Required | Default | Description                                                                                                  |
|-----------|------|----------|---------|--------------------------------------------------------------------------------------------------------------|
| `baseUrl` | String | Yes | _None_ | Small Pics base URL for this origin.                                                                         |
| `secret` | String or `null` | No | `null` | Signing secret for this origin. Required if signed requests are enabled for your image source in Small Pics. |
| `transformSvgs` | Boolean | No | `false` | Transform SVGs for this origin.                                                                              |
| `transformAnimatedGifs` | Boolean | No | `true` | Transform animated GIFs for this origin.                                                                     |
| `defaultParams` | Array | No | `[]` | Transform parameters applied after global defaults and before per-transform parameters.                      |

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
        'q' => 65,
    ],
];
```

### Multiple Origins

Use origin labels to select the source setup for an image. The label is only used by the plugin and is not added to generated URLs.

See an example of selecting an origin in the [Twig section](#twig) below.

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

### Parameter Precedence

Later values override earlier values.

- Direct calls: global `defaultParams`, origin `defaultParams`, then the params passed to `transformImage()` or `srcset()`.
- Native Craft transforms: global `defaultParams`, origin `defaultParams`, the Craft transform config, then `nativeTransformsParams`.
- Thumbnails: global `defaultParams`, origin `defaultParams`, the generated thumbnail dimensions and mode, then `thumbnailParams`.

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

### Transform an Image

`transformImage()` returns a `TransformedImage`. Rendering it as a string outputs the URL.

```twig
{% set image = craft.smallpics.transformImage(asset, {
    w: 800,
    h: 600,
    fit: 'cover',
    q: 80
}) %}

<img
    src="{{ image }}"
    width="{{ image.width }}"
    height="{{ image.height }}"
>
```

Renders:

```html
<img src="https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=600&q=80&w=800" width="800" height="600">
```

#### Use a Named Transform

You can also pass a named Craft transform handle as the config.

```twig
<img src="{{ craft.smallpics.transformImage(asset, 'hero') }}">
```

Renders:

```html
<img src="https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=600&w=800">
```

#### Select an Origin

Select an origin with `origin`.

```twig
{{ craft.smallpics.transformImage(asset, {
    origin: 'editorialImages',
    w: 1200
}) }}
```

Renders:

```html
https://my-source.smallpics.io/bird.jpg?w=1200
```

#### Set an Output Format

For format selection, see the note in [Transform Options](#transform-options).[^format-selection]

```twig
<img src="{{ craft.smallpics.transformImage(asset, {
    w: 1200,
    fm: 'avif'
}) }}">
```

Renders:

```html
<img src="https://my-source.smallpics.io/bird.jpg?fm=avif&w=1200">
```

### Create a Srcset

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
        fit: 'cover'
    }
) }}">
```

Renders:

```html
<img srcset="https://my-source.smallpics.io/bird.jpg?dpr=1&fit=cover-center&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=cover-center&h=300&w=400 2x, https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=800 800w">
```

#### Set a Fallback Source

If you need a fallback `src` value, you can reuse one of the transformed images
from the generated srcset instead of creating a separate transform.

```twig
{% set srcset = craft.smallpics.srcset(
    asset,
    {
        '1x': { dpr: 1 },
        '2x': { dpr: 2 },
        '800w': { w: 800 }
    },
    {
        w: 400,
        h: 300,
        fit: 'cover'
    }
) %}

<img
    src="{{ srcset['800w'] }}"
    srcset="{{ srcset }}"
    alt="{{ asset.alt }}"
>
```

Renders:

```html
<img src="https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=800" srcset="https://my-source.smallpics.io/bird.jpg?dpr=1&fit=cover-center&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=cover-center&h=300&w=400 2x, https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=800 800w" alt="Bird">
```

#### Access Srcset Images

The `srcset` result can be accessed like an array. Use the same descriptor keys
you passed to `srcset()`, such as `800w`, `1x`, or `2x`. Each item is a
`TransformedImage`, so it can be cast to a string or used via `getUrl()` when you
only need the URL.

## Transform Options

All the transform options supported by the Small Pics transform API are supported by this plugin. Take a look at the [Small Pics docs](https://www.smallpics.io/docs/) for more detailed information about each parameter.

Transform options can use either the Small Pics URL param key or the option name used by `smallpics/smallpics-php`. For example, `q` and `quality` are equivalent.

The examples below use PHP array syntax. Use the equivalent object or array syntax in Twig templates.

Use a single value for setters that take one argument:

```php
[
    'w' => 800,
    'q' => 80,
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

| Query parameter | Plugin option name  | Value                                                                                                                      | Example                                                  | Setter                                                                                                                                                      |
|-----------------|---------------------|----------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `or`            | `orientation`       | `0`, `90`, `180`, `270`, or `auto`                                                                                         | `'or' => 'auto'`                                         | `setOrientation(int\|string $orientation)`                                                                                                                  |
| `flip`          | `flip`              | `v`, `h`, or `both`                                                                                                        | `'flip' => 'h'`                                          | `setFlip(string $flip)`                                                                                                                                     |
| `crop`          | `crop`              | `[width, height, x, y]`                                                                                                    | `'crop' => [400, 300, 10, 20]`                           | `setCrop(int $width, int $height, int $x, int $y)`                                                                                                          |
| `w`             | `width`             | Integer width                                                                                                              | `'w' => 800`                                             | `setWidth(int $width)`                                                                                                                                      |
| `h`             | `height`            | Integer height                                                                                                             | `'h' => 600`                                             | `setHeight(int $height)`                                                                                                                                    |
| `ar`            | `aspectRatio`       | Ratio number, or `[dividend, divisor]`                                                                                     | `ar: 4 / 3`, `ar: 1.778`, or `'ar' => [16, 9]`           | `setAspectRatio(int\|float $dividend, null\|int\|float $divisor = null)`                                                                                    |
| `fit`[^focal-point-crops] | `fit`        | `contain`, `max`, `fill`, `fill-max`, `stretch`, `cover`, or `crop`; cover crop positions: `cover-top-left`, `cover-top`, `cover-top-right`, `cover-left`, `cover-center`, `cover-right`, `cover-bottom-left`, `cover-bottom`, or `cover-bottom-right`; or `[fit, cropPosition, focalPointX, focalPointY, zoom]` | `'fit' => ['cover', 'cover-top']` | `setFit(string\|Fit $fit, null\|string\|CropPosition $cropPosition = null, ?int $focalPointX = null, ?int $focalPointY = null, ?int $zoom = null)` |
| `dpr`           | `devicePixelRatio`  | Integer device pixel ratio                                                                                                 | `'dpr' => 2`                                             | `setDevicePixelRatio(int $devicePixelRatio = 1)`                                                                                                            |
| `bri`           | `brightness`        | Integer brightness                                                                                                         | `'bri' => 10`                                            | `setBrightness(int $brightness)`                                                                                                                            |
| `con`           | `contrast`          | Integer contrast                                                                                                           | `'con' => 15`                                            | `setContrast(int $contrast)`                                                                                                                                |
| `gam`           | `gamma`             | Float gamma                                                                                                                | `'gam' => 1.2`                                           | `setGamma(float $gamma)`                                                                                                                                    |
| `sharp`         | `sharpen`           | Integer sharpen amount                                                                                                     | `'sharp' => 20`                                          | `setSharpen(int $sharpen)`                                                                                                                                  |
| `blur`          | `blur`              | Integer blur amount                                                                                                        | `'blur' => 5`                                            | `setBlur(int $blur)`                                                                                                                                        |
| `pixel`         | `pixelate`          | Integer pixelate amount                                                                                                    | `'pixel' => 8`                                           | `setPixelate(int $pixelate)`                                                                                                                                |
| `filt`          | `filter`            | `grayscale` or `sepia`                                                                                                     | `'filt' => 'grayscale'`                                  | `setFilter(string\|Filter $filter)`                                                                                                                         |
| `mark`          | `watermarkPath`     | Watermark image path                                                                                                       | `'mark' => '/watermark.png'`                             | `setWatermarkPath(string $watermarkPath)`                                                                                                                   |
| `markorigin`    | `watermarkOrigin`   | Watermark origin name                                                                                                      | `'markorigin' => 'default'`                              | `setWatermarkOrigin(string $watermarkOrigin)`                                                                                                               |
| `markw`         | `watermarkWidth`    | Integer width or relative width string                                                                                     | `'markw' => 120`                                         | `setWatermarkWidth(int\|string $watermarkWidth)`                                                                                                            |
| `markh`         | `watermarkHeight`   | Integer height or relative height string                                                                                   | `'markh' => 80`                                          | `setWatermarkHeight(int\|string $watermarkHeight)`                                                                                                          |
| `markfit`[^focal-point-crops] | `watermarkFit` | `contain`, `max`, `fill`, `fill-max`, `stretch`, `cover`, or `crop`; cover crop positions: `cover-top-left`, `cover-top`, `cover-top-right`, `cover-left`, `cover-center`, `cover-right`, `cover-bottom-left`, `cover-bottom`, or `cover-bottom-right`; or `[fit, cropPosition, focalPointX, focalPointY, zoom]` | `'markfit' => 'contain'` | `setWatermarkFit(string\|Fit $fit, null\|string\|CropPosition $cropPosition = null, ?int $focalPointX = null, ?int $focalPointY = null, ?int $zoom = null)` |
| `markx`         | `watermarkXOffset`  | Integer offset or relative offset string                                                                                   | `'markx' => 20`                                          | `setWatermarkXOffset(int\|string $watermarkXOffset)`                                                                                                        |
| `marky`         | `watermarkYOffset`  | Integer offset or relative offset string                                                                                   | `'marky' => 20`                                          | `setWatermarkYOffset(int\|string $watermarkYOffset)`                                                                                                        |
| `markpad`       | `watermarkPadding`  | Integer padding or relative padding string                                                                                 | `'markpad' => 16`                                        | `setWatermarkPadding(int\|string $watermarkPadding)`                                                                                                        |
| `markpos`       | `watermarkPosition` | `top-left`, `top`, `top-right`, `left`, `center`, `right`, `bottom-left`, `bottom`, or `bottom-right`                      | `'markpos' => 'bottom-right'`                            | `setWatermarkPosition(string\|WatermarkPosition $watermarkPosition)`                                                                                        |
| `markalpha`     | `watermarkAlpha`    | Integer alpha                                                                                                              | `'markalpha' => 80`                                      | `setWatermarkAlpha(int $watermarkAlpha)`                                                                                                                    |
| `bg`            | `background`        | Background color string                                                                                                    | `'bg' => 'ffffff'`                                       | `setBackground(string $background)`                                                                                                                         |
| `border`        | `border`            | `[width, color, method]`, where method is `overlay`, `shrink`, or `pad`                                                    | `'border' => [8, 'ffffff', 'pad']`                       | `setBorder(int\|string $width, string $color, string\|BorderMethod $borderMethod)`                                                                          |
| `q`             | `quality`           | Integer quality                                                                                                            | `'q' => 80`                                              | `setQuality(int $quality)`                                                                                                                                  |
| `fm`[^format-selection] | `format`     | `jpg`, `pjpg`, `png`, `gif`, `webp`, `avif`, or `jxl`                                                                      | `'fm' => 'gif'`                                          | `setFormat(string\|Format $format)`                                                                                                                         |
| `interlace`     | `interlaced`        | Boolean                                                                                                                    | `'interlace' => true`                                    | `setInterlaced(bool $interlaced)`                                                                                                                           |

[^format-selection]: **Format selection.** Unless you specifically need a format, omit `fm` or `format` from transforms. Small Pics uses the request's `Accept` header to choose the output format when one is present. If you set `fm` but the requested format is not accepted by the `Accept` header, Small Pics uses the header to choose the format instead. If neither a format nor an `Accept` header is present, it defaults to AVIF.

[^focal-point-crops]: **Focal-point crops.** For `fit` or `markfit`, pass `null` as the crop position: `'fit' => ['crop', null, 50, 50]` or `'fit' => ['crop', null, 50, 50, 2]`.

## PHP

```php
use smallpics\craft\Plugin;

$image = Plugin::$instance->transformer->transformImage(
    $asset, 
    [
        'w' => 800,
        'h' => 600,
    ]
);

$url = (string) $image; // 'https://my-source.smallpics.io/bird.jpg?h=600&w=800'
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
// 'https://my-source.smallpics.io/bird.jpg?dpr=1&fit=cover-center&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=cover-center&h=300&w=400 2x'
```

## Reference

### TransformedImage

`transformImage()` returns a `smallpics\craft\models\TransformedImage`.

```php
use smallpics\craft\Plugin;
use smallpics\craft\models\TransformedImage;

/** @var TransformedImage $image */
$image = Plugin::$instance->transformer->transformImage($asset, [
    'w' => 800,
    'h' => 600,
    'fit' => 'cover',
]);

$url = (string) $image; // 'https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=600&w=800'
$url = $image->getUrl(); // 'https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=600&w=800'
$width = $image->getWidth(); // 800
$height = $image->getHeight(); // 600
$mimeType = $image->getMimeType(); // 'image/jpeg'
$sourceAsset = $image->getSource(); // The original Craft asset.
$config = $image->getConfig(); // ['w' => 800, 'h' => 600, 'fit' => 'cover']
$options = $image->getOptions(); // The Small Pics Options object.
```

### TransformedSrcset

`srcset()` returns a `smallpics\craft\models\TransformedSrcset`.

This model is read-only.

```php
use smallpics\craft\Plugin;
use smallpics\craft\models\TransformedImage;
use smallpics\craft\models\TransformedSrcset;

/** @var TransformedSrcset $srcset */
$srcset = Plugin::$instance->transformer->srcset(
    $asset,
    [
        '400w' => ['w' => 400],
        '800w' => ['w' => 800],
    ],
    [
        'h' => 300,
        'fit' => 'cover',
    ]
);

$srcsetValue = (string) $srcset;
// 'https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=400 400w, https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=800 800w'

/** @var TransformedImage $smallImage */
$smallImage = $srcset['400w']; // The transformed 400px-wide image.
$smallImageUrl = (string) $smallImage; // 'https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=400'

foreach ($srcset as $descriptor => $image) {
    $url = $image->getUrl(); // 'https://my-source.smallpics.io/bird.jpg?fit=cover-center&h=300&w=400' for '400w'.
}
```
