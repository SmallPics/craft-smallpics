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

See the [Small Pics docs](https://www.smallpics.io/docs) for the full list of supported transform parameters.

Craft transform keys are translated to Small Pics keys when native transforms are intercepted:

| Craft key  | Small Pics param        |
|------------|-------------------------|
| `width`    | `w`                     |
| `height`   | `h`                     |
| `quality`  | `q`                     |
| `mode`     | `fit`                   |
| `position` | cover position in `fit` |
| `fill`     | `bg`                    |

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
