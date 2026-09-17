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
			$values['sources'] = [];
		}


		$sources = $values['sources'];

		if ($sources === [] && ! empty($values['baseUrl'])) {
			$sources[self::DEFAULT_SOURCE_NAME] = new SourceConfig([
				'baseUrl' => $values['baseUrl'],
				'secret' => $values['secret'] ?? null,
				'transformSvgs' => $values['transformSvgs'] ?? false,
				'transformAnimatedGifs' => $values['transformAnimatedGifs'] ?? true,
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

		parent::setAttributes($values, $safeOnly);
	}
}
