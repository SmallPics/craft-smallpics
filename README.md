![header.svg](assets/header.svg)

# Small Pics for Craft CMS

Add [Small Pics](https://www.smallpics.io) image CDN and transforms to Craft CMS.

## Upgrading

- [v1 to v2](migrating-v1-v2.md) upgrade guide

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

Create `config/smallpics.php` in the root of your project, or copy and rename [config.php](src/config.php).

Each source needs a `baseUrl`.

### Configuration parameters

| Parameter | Type | Required | Default | Description                                                                                                                    |
|-----------|------|----------|---------|--------------------------------------------------------------------------------------------------------------------------------|
| `transformNativeImages` | Boolean | No | `true` | Use Small Pics for Craft's native image transforms.                                                                            |
| `transformThumbnails` | Boolean | No | `true` | Use Small Pics for Craft thumbnail URLs and asset preview images.                                                              |
| `thumbnailParams` | Array | No | `[]` | Transform parameters applied to thumbnails and asset previews after global and source defaults.                                |
| `nativeTransformsParams` | Array | No | `[]` | Transform parameters applied to native Craft transforms after global and source defaults.                                      |
| `excludedVolumes` | Array | No | `[]` | Volume handles. The plugin skips native transforms, thumbnails and asset previews for assets in these volumes. `transformImage()` and `srcset()` ignore this setting. Use it for volumes that no Small Pics source fetches from. |
| `defaultSource` | String | No | First source | Fallback source. See [Multiple Sources](#multiple-sources). |
| `baseUrl` | String | Yes | _None_ | Small Pics base URL for the default single source. **Required when `sources` is empty.**                                       |
| `secret` | String or `null` | No | `null` | Signing secret for the default single source. **Required if your Small Pics image source has signed requests turned on.** |
| `transformSvgs` | Boolean | No | `false` | Transform SVGs for the default single source.                                                                                  |
| `transformAnimatedGifs` | Boolean | No | `true` | Transform animated GIFs for the default single source.                                                                         |
| `sources` | Array | No | `[]` | See [Source configuration](#source-configuration).                                                                             |
| `defaultParams` | Array | No | `[]` | Transform parameters applied to every request before source and per-transform parameters.                                      |

### Source configuration

| Parameter | Type | Required | Default | Description                                                                                                  |
|-----------|------|----------|---------|--------------------------------------------------------------------------------------------------------------|
| `baseUrl` | String | Yes | _None_ | Small Pics base URL for this source. |
| `secret` | String or `null` | No | `null` | Signing secret for this source. Required if your Small Pics image source has signed requests turned on. |
| `transformSvgs` | Boolean | No | `false` | Transform SVGs for this source. |
| `transformAnimatedGifs` | Boolean | No | `true` | Transform animated GIFs for this source. |
| `defaultParams` | Array | No | `[]` | Transform parameters applied after global defaults and before per-transform parameters.                      |
| `volumes` | Array | No | `[]` | Volume handles. Assets in these volumes use this source. |

### Single Source

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
    'baseUrl' => 'https://my-source.smallpics.io',
    'secret' => getenv('SMALLPICS_SECRET') ?: null,
    'transformSvgs' => false,
    'transformAnimatedGifs' => false,
    'defaultParams' => [
        'q' => 65,
    ],
];
```

### Multiple Sources

Assets in a source's `volumes` use that source. Other assets use `defaultSource`. If two sources list the same volume, the plugin uses the first.

To pick a source for one transform, pass its label as `source`, which overrides both. The label is not added to generated URLs. For an example, see [Select a Source](#select-a-source).

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
    'defaultSource' => 'productImages',
    'sources' => [
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
            'volumes' => ['editorial'],
        ],
    ],
];
```

### Parameter Precedence

Later values override earlier values.

- Direct calls: global `defaultParams`, source `defaultParams`, then the params passed to `transformImage()` or `srcset()`.
- Native Craft transforms: global `defaultParams`, source `defaultParams`, the Craft transform config, then `nativeTransformsParams`.
- Thumbnails and asset previews: global `defaultParams`, source `defaultParams`, the generated dimensions and mode, then `thumbnailParams`.

## Native Craft Transforms

When `transformNativeImages` is on, the plugin handles Craft's native image transforms. Use `nativeTransformsParams` to set Small Pics params for native transforms only.

For example, to give all native transforms a lower quality than the default:

```php
return [
    'transformNativeImages' => true,
    'nativeTransformsParams' => [
        'q' => 55, // Give all native transforms a lower quality.
    ],
    // ...
];
```

Native transforms are any transform applied to images automatically by Craft, or through native Craft operations. For example:

```twig
{{ asset.getUrl({ width: 800, height: 600, mode: 'crop' }) }}
{{ asset.getImg('hero') }}
{{ asset.getSrcset(['400w', '800w'], { width: 800 }) }}
```

With `transformThumbnails` enabled, thumbnails and asset previews (up to 2560px) use Small Pics. Override their params with `thumbnailParams`.

```php
return [
    'transformThumbnails' => true,
    'thumbnailParams' => [
        'q' => 50, // Give thumbnails the lowest quality.
    ],
    // ...
];
```

### Native Transform Key Mapping

For native transforms, the plugin maps Craft transform keys to Small Pics keys:

| Craft key  | Small Pics param        |
|------------|-------------------------|
| `width`    | `w`                     |
| `height`   | `h`                     |
| `quality`  | `q`                     |
| `mode`     | `fit`                   |
| `position` | named anchor in `crop`, or `fp` (see [Focal Points](#focal-points)) |
| `fill`     | `bg`                    |

## Focal Points

For crop transforms, the plugin sends the asset's focal point as `fp` in place of `position`, matching Craft. To override the focal point, set `crop` or `fp` in the params. Without a focal point, the crop uses `position`, or the center when `position` is not set.

```twig
{# Crops around the asset's focal point #}
{{ craft.smallpics.transformImage(asset, { w: 800, h: 600, fit: 'crop' }) }}

{# Crops from the center #}
{{ craft.smallpics.transformImage(asset, { w: 800, h: 600, fit: 'crop', crop: 'center' }) }}
```

## Twig

### Transform an Image

`transformImage()` returns a `TransformedImage`. Get its URL with `getUrl()`, or render the image as a string.

```twig
{% set image = craft.smallpics.transformImage(asset, {
    w: 800,
    h: 600,
    fit: 'crop',
    q: 80
}) %}

<img
    src="{{ image }}"
    width="{{ image.width }}"
    height="{{ image.height }}"
>

<!-- alternatively, use the getUrl() method directly. -->

<img 
    src="{{ image.getUrl() }}"
    width="{{ image.width }}"
    height="{{ image.height }}"
>
```

Both variations render:

```html
<img
    src="https://my-source.smallpics.io/bird.jpg?fit=crop&h=600&q=80&w=800"
    width="800"
    height="600"
>
```

#### Use a Named Transform

You can also pass a named Craft transform handle as the config.

```twig
<img src="{{ craft.smallpics.transformImage(asset, 'hero') }}">
```

Renders:

```html
<img src="https://my-source.smallpics.io/bird.jpg?crop=center&fit=crop&h=600&w=800">
```

For an asset with a focal point, the URL has `fp` in place of `crop`.

#### Select a Source

Select a source with `source`.

```twig
{{ craft.smallpics.transformImage(asset, {
    source: 'editorialImages',
    w: 1200
}) }}
```

Assuming a baseUrl of `https://editorial-images.smallpics.io`, that would render:

```html
https://editorial-images.smallpics.io/bird.jpg?w=1200
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
        fit: 'crop'
    }
) }}">
```

Renders:

```html
<img
    srcset="https://my-source.smallpics.io/bird.jpg?dpr=1&fit=crop&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=crop&h=300&w=400 2x, https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=800 800w"
>
```

#### Set a Fallback `src`

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
        fit: 'crop'
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
<img
    src="https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=800"
    srcset="https://my-source.smallpics.io/bird.jpg?dpr=1&fit=crop&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=crop&h=300&w=400 2x, https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=800 800w"
    alt="Bird"
>
```

#### Access Srcset Images

Access the `srcset` result like an array. Use the descriptor keys you passed to
`srcset()`, such as `800w`, `1x`, or `2x`. Each item is a `TransformedImage`. Cast
it to a string or call `getUrl()` to get the URL.

## Transform Options

The plugin supports every option in the Small Pics transform API. See the [Small Pics docs](https://www.smallpics.io/docs/) for each parameter. Try them on an image in the [Small Pics playground](https://www.smallpics.io/playground/).

Transform options can use either the Small Pics URL param key or the option name used by `smallpics/smallpics-php:^2.0.0`. For example, `q` and `quality` are equivalent.

The examples below use PHP array syntax. Use the equivalent object or array syntax in Twig templates.

Use a single value for options that accept a single argument:

```php
[
    'w' => 800,
    'q' => 80,
]
```

Use an array for options that accept multiple arguments:

```php
[
    'crop' => [400, 300, 10, 20],
    'ar' => [16, 9],
    'border' => [8, 'ffffff', 'expand'],
    'fit' => 'crop',
    'fp' => '50w:0h',
]
```

| Query parameter | Plugin option name    | Accepted values                                                                     | Example                               |
|-----------------|-----------------------|-------------------------------------------------------------------------------------|---------------------------------------|
| `or`            | `orientation`         | `0`, `90`, `180`, `270`, or `auto`                                                  | `'or' => 'auto'`                      |
| `flip`          | `flip`                | `v`, `h`, or `both`                                                                 | `'flip' => 'h'`                       |
| `crop`          | `crop`                | Named anchor, `entropy`, `face[,fallback]`, `facesarea[,fallback]`, or `[width, height, x, y]` | `'crop' => [400, 300, 10, 20]`        |
| `w`             | `width`               | Integer or decimal pixels, or relative dimensions                                   | `'w' => '65p'`                        |
| `h`             | `height`              | Integer or decimal pixels, or relative dimensions                                   | `'h' => '50w'`                        |
| `ar`            | `aspectRatio`         | `width:height`, decimal ratio, or `[dividend, divisor]`                             | `'ar' => '16:9'`                      |
| `fit`           | `fit`                 | `contain`, `max`, `fill`, `fill-max`, `stretch`, or `crop`                          | `'fit' => 'crop'`                     |
| `dpr`           | `devicePixelRatio`    | Integer or decimal                                                                  | `'dpr' => 1.5`                        |
| `bri`           | `brightness`          | Integer brightness                                                                  | `'bri' => 10`                         |
| `con`           | `contrast`            | Integer contrast                                                                    | `'con' => 15`                         |
| `gam`           | `gamma`               | Float gamma                                                                         | `'gam' => 1.2`                        |
| `sharp`         | `sharpen`             | Integer sharpen amount                                                              | `'sharp' => 20`                       |
| `blur`          | `blur`                | Integer blur amount                                                                 | `'blur' => 5`                         |
| `pixel`         | `pixelate`            | Integer pixelate amount                                                             | `'pixel' => 8`                        |
| `filt`          | `filter`              | `grayscale` or `sepia`                                                              | `'filt' => 'grayscale'`               |
| `mark`          | `watermarkPath`       | Watermark image path                                                                | `'mark' => '/watermark.png'`          |
| `markorigin`    | `watermarkOrigin`     | Watermark origin name                                                               | `'markorigin' => 'default'`           |
| `markw`         | `watermarkWidth`      | Integer, decimal, or relative width                                                 | `'markw' => '20w'`                    |
| `markh`         | `watermarkHeight`     | Integer, decimal, or relative height                                                | `'markh' => '20h'`                    |
| `markfit`       | `watermarkFit`        | `contain`, `max`, `fill`, `fill-max`, `stretch`, or `crop`                          | `'markfit' => 'contain'`              |
| `markfp`        | `watermarkFocalPoint` | Pixels, relative values, or `x:y` within the watermark                              | `'markfp' => '20p:20p'`               |
| `markzoom`      | `watermarkZoom`       | Numeric zoom from `1` to `100`                                                      | `'markzoom' => 2`                     |
| `markpad`       | `watermarkPadding`    | Pixels, relative values, or `x:y`                                                   | `'markpad' => '10:20'`                |
| `markpos`       | `watermarkPosition`   | Named anchor, numeric coordinate, or pixel/relative `x:y` string                    | `'markpos' => '25p:50p'`              |
| `markalpha`     | `watermarkAlpha`      | Integer alpha                                                                       | `'markalpha' => 80`                   |
| `bg`            | `background`          | Background color                                                                    | `'bg' => 'ffffff'`                    |
| `border`        | `border`              | `[width, color, method]`; method is `overlay`, `shrink`, or `expand`                | `'border' => [8, 'ffffff', 'expand']` |
| `trim` | `trim` | `auto`, `color`, `alpha`, or `either` | `'trim' => 'color'` |
| `trim-color` | `trimColor` | Color to remove | `'trim-color' => 'ffffff'` |
| `trim-alpha` | `trimAlpha` | Alpha threshold, 0 to 255 | `'trim-alpha' => 8` |
| `trim-tol` | `trimTolerance` | Tolerance, 0 to 255 | `'trim-tol' => 8` |
| `trim-pad` | `trimPadding` | Padding in pixels or relative values[^relative-values] | `'trim-pad' => 8` |
| `trim-md` | `trimMeanDifference` | Auto mean difference, 0 to 255 | `'trim-md' => 8` |
| `trim-sd` | `trimStandardDeviation` | Auto standard deviation, 0 to 255 | `'trim-sd' => 8` |
| `q`             | `quality`             | Integer quality                                                                     | `'q' => 80`                           |
| `fm`            | `format`              | `strict`, `auto`, `jpg`, `jpeg`, `pjpg`, `png`, `gif`, `webp`, `avif`, or `jxl` [^format-selection]   | `'fm' => 'avif'`                      |
| `interlace`     | `interlaced`          | Boolean                                                                             | `'interlace' => true`                 |
| `fp`            | `focalPoint`          | Pixels or relative x/y                                                              | `'fp' => '25w:75h'`                   |
| `zoom`          | `zoom`                | Numeric, `face`, `facesarea`, optional numeric fallback                             | `'zoom' => 'face,2.5'`                |
| `zoompad`       | `zoomPadding`         | Pixels or relative x/y                                                              | `'zoompad' => '10:20'`                |
| `face`          | `face`                | One-based face index                                                                | `'face' => 1`                         |
| `debug`         | `debug`               | Boolean                                                                             | `'debug' => true`                     |
| `passthrough`   | `passthrough`         | Boolean; false removes the flag unless `transformSvgs` is false                     | `'passthrough' => true`               |

Dimensions accept decimal pixels and `p`, `w`, or `h` units.[^relative-values] Paired values accept serialized `x:y` strings.

```twig
{% set image = craft.smallpics.transformImage(asset, {
    w: '65p', fit: 'crop', crop: 'face,top',
    zoom: 'face,2.5', zoompad: '5p:10p'
}) %}
```

[^format-selection]: **Format selection.** Omitting `fm` or `format` uses `strict`. With `fm=strict`, a missing or catch-all `Accept` header (`*/*` or `image/*`) preserves the source format. A specific `Accept` header selects the output format. `fm=auto` always selects a format from `Accept`, falling back to AVIF. GIF inputs use WebP in auto mode, which supports animation.

[^relative-values]: **Relative values.** Use a percentage from 0 to 100 followed by `w` for width, `h` for height, or `p` for the relevant axis. For example, `5w` means 5% of the base image's width, and `35h` means 35% of its height.

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
        'fit' => 'crop',
    ]
);

$srcsetValue = (string) $srcset;
// 'https://my-source.smallpics.io/bird.jpg?dpr=1&fit=crop&h=300&w=400 1x, https://my-source.smallpics.io/bird.jpg?dpr=2&fit=crop&h=300&w=400 2x'
```

## Reference

### TransformedImage

`transformImage()` returns a `smallpics\craft\models\TransformedImage`.

`getMimeType()` returns an empty string when the output format depends on the image request's `Accept` header, for example when using `fm=strict` or `fm=auto`. Set an explicit format for a known MIME type.

```php
use smallpics\craft\Plugin;
use smallpics\craft\models\TransformedImage;

/** @var TransformedImage $image */
$image = Plugin::$instance->transformer->transformImage($asset, [
    'w' => 800,
    'h' => 600,
    'fit' => 'crop',
    'fm' => 'jpg',
]);

$url = (string) $image; // 'https://my-source.smallpics.io/bird.jpg?fit=crop&fm=jpg&h=600&w=800'
$url = $image->getUrl(); // 'https://my-source.smallpics.io/bird.jpg?fit=crop&fm=jpg&h=600&w=800'
$width = $image->getWidth(); // 800
$height = $image->getHeight(); // 600
$mimeType = $image->getMimeType(); // 'image/jpeg'
$sourceAsset = $image->getSource(); // The original Craft asset.
$config = $image->getConfig(); // ['w' => 800, 'h' => 600, 'fit' => 'crop', 'fm' => 'jpg']
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
        'fit' => 'crop',
    ]
);

$srcsetValue = (string) $srcset;
// 'https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=400 400w, https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=800 800w'

/** @var TransformedImage $smallImage */
$smallImage = $srcset['400w']; // The transformed 400px-wide image.
$smallImageUrl = (string) $smallImage; // 'https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=400'

foreach ($srcset as $descriptor => $image) {
    $url = $image->getUrl(); // 'https://my-source.smallpics.io/bird.jpg?fit=crop&h=300&w=400' for '400w'.
}
```

### SVG passthrough

`transformSvgs` defaults to `false`. With it off, Small Pics returns SVGs without transforms. The response is cacheable like other transformed images.

This setting takes precedence over `passthrough: false` in transform parameters.

```php
// Source configuration (also supported at the root for a single source).
'sources' => [
    'default' => [
        'baseUrl' => 'https://images.example.com',
        'transformSvgs' => false, // Default: adds passthrough=1.
    ],
],
```

```twig
{% set image = craft.smallpics.transformImage(asset, { width: 800, passthrough: true }) %}
```
