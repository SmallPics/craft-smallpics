<?php

namespace smallpics\craft\services;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\ImageTransforms;
use craft\models\ImageTransform;
use smallpics\craft\helpers\FileHelper;
use smallpics\craft\models\SourceConfig;
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
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function transformImage(Asset $image, array|string|ImageTransform $config = []): TransformedImage
	{
		$config = $this->normalizeTransformConfig($config);
		[$source, $config] = $this->resolveSource($config);
		$options = $this->createOptions($source, $config);
		if (! $source->transformSvgs) {
			$options->setPassthrough(true);
		}

		$sourceUrl = $this->sourceUrl($image);

		if (FileHelper::isAnimatedGif($image) && ! $source->transformAnimatedGifs) {
			return new TransformedImage($sourceUrl, $image, $options, $config);
		}

		if ($source->baseUrl === null || $source->baseUrl === '') {
			throw new InvalidConfigException('Small Pics baseUrl is missing.');
		}

		return new TransformedImage(
			(new UrlBuilder($source->baseUrl, $source->secret))->buildUrl($this->sourcePath($sourceUrl), $options),
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
	 * @return array{SourceConfig, array<string, mixed>}
	 */
	private function resolveSource(array $config): array
	{
		$settings = Plugin::settings();
		$sourceName = $settings->defaultSource;

		if (isset($config['source']) && is_scalar($config['source'])) {
			$sourceName = (string) $config['source'];
		}

		unset($config['source']);

		if ($settings->sources === []) {
			throw new InvalidConfigException('Small Pics is missing required config.');
		}

		if (! isset($settings->sources[$sourceName])) {
			throw new InvalidConfigException("Unknown Small Pics source '{$sourceName}'.");
		}

		$source = $settings->sources[$sourceName];

		if (! $source->baseUrl) {
			throw new InvalidConfigException("Small Pics baseUrl is missing for source '{$sourceName}'.");
		}

		return [$source, $config];
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function createOptions(SourceConfig $source, array $config): Options
	{
		$config = [
			...$this->normalizeOptionKeys(Plugin::settings()->defaultParams),
			...$this->normalizeOptionKeys($source->defaultParams),
			...$this->normalizeOptionKeys($config),
		];

		$config = $this->normalizeSmallPicsConfig($this->normalizeOptionKeys($config));

		$optionsConfig = [];

		foreach ($config as $key => $value) {
			$optionKey = Options::allOptions()[$key] ?? $key;
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
		foreach (['width', 'height'] as $key) {
			if (isset($config[$key])) {
				$config[$key] = $this->dimensionValue($config[$key]);
			}
		}

		if (isset($config['mode']) && is_scalar($config['mode']) && ! isset($config['fit'])) {
			$config['fit'] = $this->fitValue((string) $config['mode']);
		}

		if (isset($config['quality']) && is_scalar($config['quality'])) {
			$config['quality'] = (int) $config['quality'];
		}

		if (in_array($config['fit'] ?? null, ['crop', Fit::CROP], true) && ! isset($config['crop']) && is_string($config['position'] ?? null)) {
			$config['crop'] = self::NATIVE_POSITION_MAP[$config['position']] ?? $config['position'];
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
			$normalized[Options::allOptions()[$key] ?? $key] = $value;
		}

		return $normalized;
	}

	private function fitValue(string $mode): string
	{
		return match ($mode) {
			'fit' => Fit::CONTAIN->value,
			'stretch' => Fit::STRETCH->value,
			'letterbox' => Fit::FILL->value,
			'crop' => Fit::CROP->value,
			default => $mode,
		};
	}

	private function dimensionValue(mixed $value): int|float|string
	{
		if (! is_scalar($value)) {
			return 0;
		}

		$value = (string) preg_replace('/px$/', '', trim((string) $value));
		return is_numeric($value) ? $value + 0 : $value;
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
