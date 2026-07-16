<?php

namespace smallpics\craft\services;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\ImageTransforms;
use craft\models\ImageTransform;
use smallpics\craft\helpers\FileHelper;
use smallpics\craft\models\OriginConfig;
use smallpics\craft\models\TransformedImage;
use smallpics\craft\models\TransformedSrcset;
use smallpics\craft\Plugin;
use smallpics\smallpics\enums\CropPosition;
use smallpics\smallpics\enums\Fit;
use smallpics\smallpics\Options;
use smallpics\smallpics\UrlBuilder;
use yii\base\InvalidConfigException;

class Transformer extends Component
{
	/**
	 * array<string, CropPosition>
	 */
	private const NATIVE_POSITION_MAP = [
		'top-left' => CropPosition::TOP_LEFT,
		'top-center' => CropPosition::TOP,
		'top-right' => CropPosition::TOP_RIGHT,
		'center-left' => CropPosition::LEFT,
		'center-center' => CropPosition::CENTER,
		'center-right' => CropPosition::RIGHT,
		'bottom-left' => CropPosition::BOTTOM_LEFT,
		'bottom-center' => CropPosition::BOTTOM,
		'bottom-right' => CropPosition::BOTTOM_RIGHT,
	];

	/**
	 * @var array<string, string>
	 */
	private const PARAM_OPTION_KEYS = [
		Options::ORIENTATION => 'orientation',
		Options::FLIP => 'flip',
		Options::CROP => 'crop',
		Options::WIDTH => 'width',
		Options::HEIGHT => 'height',
		Options::FIT => 'fit',
		Options::DEVICE_PIXEL_RATIO => 'devicePixelRatio',
		Options::BRIGHTNESS => 'brightness',
		Options::CONTRAST => 'contrast',
		Options::GAMMA => 'gamma',
		Options::SHARPEN => 'sharpen',
		Options::BLUR => 'blur',
		Options::PIXELATE => 'pixelate',
		Options::FILTER => 'filter',
		Options::WATERMARK_PATH => 'watermarkPath',
		Options::WATERMARK_ORIGIN => 'watermarkOrigin',
		Options::WATERMARK_WIDTH => 'watermarkWidth',
		Options::WATERMARK_HEIGHT => 'watermarkHeight',
		Options::WATERMARK_FIT => 'watermarkFit',
		Options::WATERMARK_X_OFFSET => 'watermarkXOffset',
		Options::WATERMARK_Y_OFFSET => 'watermarkYOffset',
		Options::WATERMARK_PADDING => 'watermarkPadding',
		Options::WATERMARK_POSITION => 'watermarkPosition',
		Options::WATERMARK_ALPHA => 'watermarkAlpha',
		Options::BACKGROUND => 'background',
		Options::BORDER => 'border',
		Options::QUALITY => 'quality',
		Options::FORMAT => 'format',
		Options::INTERLACE => 'interlaced',
	];

	/**
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function transformImage(Asset $image, array|string|ImageTransform $config = []): TransformedImage
	{
		$config = $this->normalizeTransformConfig($config);
		[$origin, $config] = $this->resolveOrigin($config);
		$options = $this->createOptions($origin, $config);
		$sourceUrl = $this->sourceUrl($image);

		if ((FileHelper::isSvg($image) && ! $origin->transformSvgs) || (FileHelper::isAnimatedGif($image) && ! $origin->transformAnimatedGifs)) {
			return new TransformedImage($sourceUrl, $image, $options, $config);
		}

		if ($origin->baseUrl === null || $origin->baseUrl === '') {
			throw new InvalidConfigException('Small Pics baseUrl is missing.');
		}

		return new TransformedImage(
			(new UrlBuilder($origin->baseUrl, $origin->secret))->buildUrl($this->sourcePath($sourceUrl), $options),
			$image,
			$options,
			$config,
		);
	}

	/**
	 * @param array<string, array<string, mixed>> $descriptors
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function srcset(Asset $image, array $descriptors, array|string|ImageTransform $config = []): TransformedSrcset
	{
		$commonConfig = $this->normalizeTransformConfig($config);
		$images = [];

		foreach ($descriptors as $descriptor => $descriptorConfig) {
			$images[(string) $descriptor] = $this->transformImage($image, [
				...$commonConfig,
				...$descriptorConfig,
			]);
		}

		return new TransformedSrcset($images);
	}

	/**
	 * @param array<string, mixed>|string|ImageTransform|null $config
	 * @return array<string, mixed>
	 */
	private function normalizeTransformConfig(array|string|ImageTransform|null $config): array
	{
		if ($config instanceof ImageTransform) {
			return $this->imageTransformConfig($config);
		}

		if (is_string($config)) {
			return $this->imageTransformConfig(ImageTransforms::normalizeTransform($config));
		}

		if ($config === null) {
			return [];
		}

		if (array_key_exists('transform', $config)) {
			$baseConfig = $this->normalizeTransformConfig($this->validTransformConfig($config['transform']));
			unset($config['transform']);

			return [
				...$baseConfig,
				...$config,
			];
		}

		return $config;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function imageTransformConfig(?ImageTransform $transform): array
	{
		if (! $transform instanceof ImageTransform) {
			return [];
		}

		return array_filter([
			'width' => $transform->width,
			'height' => $transform->height,
			'format' => $transform->format,
			'mode' => $transform->mode,
			'position' => $transform->position,
			'quality' => $transform->quality,
			'fill' => $transform->fill,
		], static fn (mixed $value): bool => $value !== null);
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array{OriginConfig, array<string, mixed>}
	 */
	private function resolveOrigin(array $config): array
	{
		$settings = Plugin::settings();
		$originName = $settings->defaultOrigin;

		if (isset($config['origin']) && is_scalar($config['origin'])) {
			$originName = (string) $config['origin'];
		}

		unset($config['origin']);

		if ($settings->origins === []) {
			throw new InvalidConfigException('Small Pics is missing required config.');
		}

		if (! isset($settings->origins[$originName])) {
			throw new InvalidConfigException("Unknown Small Pics origin '{$originName}'.");
		}

		$origin = $settings->origins[$originName];

		if (! $origin->baseUrl) {
			throw new InvalidConfigException("Small Pics baseUrl is missing for origin '{$originName}'.");
		}

		return [$origin, $config];
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function createOptions(OriginConfig $origin, array $config): Options
	{
		$config = [
			...Plugin::settings()->defaultParams,
			...$origin->defaultParams,
			...$config,
		];

		$config = $this->normalizeSmallPicsConfig($this->normalizeOptionKeys($config));

		$optionsConfig = [];

		foreach ($config as $key => $value) {
			$optionKey = self::PARAM_OPTION_KEYS[$key] ?? $key;
			$optionsConfig[$optionKey] = $value;
		}

		return new Options($optionsConfig);
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array<string, mixed>
	 */
	private function normalizeSmallPicsConfig(array $config): array
	{
		foreach (['width', 'height', 'quality'] as $key) {
			if (isset($config[$key])) {
				$config[$key] = $this->dimensionValue($config[$key]);
			}
		}

		if (isset($config['mode']) && is_scalar($config['mode']) && ! isset($config['fit'])) {
			$config['fit'] = $this->fitValue((string) $config['mode'], $config['position'] ?? null);
		}

		if (isset($config['fill']) && ! isset($config['background'])) {
			$config['background'] = $config['fill'];
		}

		unset(
			$config['mode'],
			$config['position'],
			$config['fill'],
			$config['upscale'],
			$config['interlace'],
			$config['name'],
			$config['handle'],
			$config['id'],
			$config['uid'],
			$config['parameterChangeTime'],
			$config['indexId'],
		);

		return $config;
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array<string, mixed>
	 */
	private function normalizeOptionKeys(array $config): array
	{
		$normalized = [];

		foreach ($config as $key => $value) {
			$normalized[self::PARAM_OPTION_KEYS[$key] ?? $key] = $value;
		}

		return $normalized;
	}

	/**
	 * @return string|array{0: string, 1: string}
	 */
	private function fitValue(string $mode, mixed $position): string|array
	{
		return match ($mode) {
			'fit' => Fit::CONTAIN->value,
			'stretch' => Fit::STRETCH->value,
			'letterbox' => Fit::FILL->value,
			'crop' => $this->coverFitValue($position),
			default => $mode,
		};
	}

	/**
	 * @return string|array{0: string, 1: string}
	 */
	private function coverFitValue(mixed $position): string|array
	{
		if (! is_scalar($position)) {
			return Fit::COVER->value;
		}

		$cropPosition = self::NATIVE_POSITION_MAP[(string) $position] ?? null;

		return $cropPosition ? [Fit::COVER->value, $cropPosition->value] : Fit::COVER->value;
	}

	private function dimensionValue(mixed $value): int
	{
		if (! is_scalar($value)) {
			return 0;
		}

		return (int) preg_replace('/px$/', '', trim((string) $value));
	}

	/**
	 * @return array<string, mixed>|string|ImageTransform|null
	 */
	private function validTransformConfig(mixed $config): array|string|ImageTransform|null
	{
		if (is_array($config)) {
			$stringKeyedConfig = [];

			foreach ($config as $key => $value) {
				if (is_string($key)) {
					$stringKeyedConfig[$key] = $value;
				}
			}

			return $stringKeyedConfig;
		}

		if (is_string($config) || $config instanceof ImageTransform || $config === null) {
			return $config;
		}

		return [];
	}

	private function sourceUrl(Asset $image): string
	{
		return AssetsHelper::generateUrl($image);
	}

	private function sourcePath(string $sourceUrl): string
	{
		$parts = parse_url($sourceUrl);

		if ($parts === false) {
			return $sourceUrl;
		}

		$path = $parts['path'] ?? $sourceUrl;

		return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
	}
}
