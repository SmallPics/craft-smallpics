<?php

namespace smallpics\craft\models;

use craft\base\Model;

class Settings extends Model
{
	/**
	 * @var string
	 */
	public const DEFAULT_SOURCE_NAME = 'default';

	/**
	 * @deprecated Use `DEFAULT_SOURCE_NAME` instead. Will be removed in the next major release.
	 */
	public const DEFAULT_ORIGIN_NAME = self::DEFAULT_SOURCE_NAME;

	/**
	 * Whether Craft's native image transforms should use Small Pics.
	 */
	public bool $transformNativeImages = true;

	/**
	 * Whether Craft thumbnail URLs should use Small Pics.
	 */
	public bool $transformThumbnails = true;

	/**
	 * Parameters to apply to thumbnail transforms.
	 *
	 * These are applied after global and source defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $thumbnailParams = [];

	/**
	 * Parameters to apply to native Craft image transforms.
	 *
	 * These are applied after global and source defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $nativeTransformsParams = [];

	/**
	 * Name of the default source to use when none is specified.
	 */
	public string $defaultSource = self::DEFAULT_SOURCE_NAME;

	/**
	 * @deprecated Use `defaultSource` instead. Will be removed in the next major release.
	 */
	public string $defaultOrigin = self::DEFAULT_SOURCE_NAME;

	/**
	 * Root-level single-source config.
	 */
	public ?string $baseUrl = null;

	/**
	 * Root-level single-source signing secret.
	 */
	public ?string $secret = null;

	/**
	 * Root-level single-source SVG setting.
	 */
	public bool $transformSvgs = false;

	/**
	 * Root-level single-source animated GIF setting.
	 */
	public bool $transformAnimatedGifs = true;

	/**
	 * Map of sources.
	 *
	 * Example:
	 *
	 * [
	 *     'default' => [
	 *         'baseUrl' => '...',
	 *         'secret' => '...',
	 *         'defaultParams' => ['format' => 'avif'],
	 *         'transformSvgs' => true,
	 *         'transformAnimatedGifs' => false,
	 *     ],
	 *     'spaces' => [
	 *         'baseUrl' => '...',
	 *         'secret' => '...',
	 *         'defaultParams' => ['format' => 'avif'],
	 *         'transformSvgs' => false,
	 *         'transformAnimatedGifs' => false,
	 *     ],
	 * ]
	 *
	 * @var array<string, SourceConfig>
	 */
	public array $sources = [];

	/**
	 * @deprecated Use `sources` instead. Will be removed in the next major release.
	 * @var array<string, SourceConfig>
	 */
	public array $origins = [];

	/**
	 * Global default parameters for Small Pics transformations.
	 * These are applied in addition to any per-source defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $defaultParams = [];

	/**
	 * @param array<string, mixed> $values
	 * @param bool $safeOnly
	 */
	public function setAttributes($values, $safeOnly = true): void
	{
		if (! isset($values['sources']) || ! is_array($values['sources'])) {
			$values['sources'] = isset($values['origins']) && is_array($values['origins']) ? $values['origins'] : [];
		}

		if (! array_key_exists('defaultSource', $values) && isset($values['defaultOrigin']) && is_scalar($values['defaultOrigin'])) {
			$values['defaultSource'] = (string) $values['defaultOrigin'];
		}

		$sources = $values['sources'];

		if ($sources === [] && ! empty($values['baseUrl'])) {
			$sources[self::DEFAULT_SOURCE_NAME] = new SourceConfig([
				'baseUrl' => $values['baseUrl'],
				'secret' => $values['secret'] ?? null,
				'transformSvgs' => $values['transformSvgs'] ?? false,
				'transformAnimatedGifs' => $values['transformAnimatedGifs'] ?? false,
			]);
		}

		foreach ($sources as $key => $sourceConfig) {
			if (is_array($sourceConfig)) {
				$sources[(string) $key] = new SourceConfig($sourceConfig);
			}
		}

		if (! array_key_exists('defaultSource', $values) && $sources !== []) {
			$values['defaultSource'] = (string) array_key_first($sources);
		}

		$values['sources'] = $sources;
		$values['origins'] = $sources;
		$values['defaultOrigin'] = $values['defaultSource'] ?? self::DEFAULT_SOURCE_NAME;

		parent::setAttributes($values, $safeOnly);
	}
}
