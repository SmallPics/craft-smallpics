<?php

namespace smallpics\craft\models;

use craft\base\Model;

class Settings extends Model
{
	/**
	 * @var string
	 */
	public const DEFAULT_ORIGIN_NAME = 'default';

	/**
	 * Whether Craft's native image transforms should use Small Pics.
	 */
	public bool $transformNativeImages = true;

	/**
	 * Whether Craft thumbnail URLs should use Small Pics.
	 */
	public bool $transformThumbnails = false;

	/**
	 * Parameters to apply to thumbnail transforms.
	 *
	 * These are applied after global and origin defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $thumbnailParams = [];

	/**
	 * Parameters to apply to native Craft image transforms.
	 *
	 * These are applied after global and origin defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $nativeTransformsParams = [];

	/**
	 * Name of the default origin to use when none is specified.
	 */
	public string $defaultOrigin = self::DEFAULT_ORIGIN_NAME;

	/**
	 * Root-level single-origin config.
	 */
	public ?string $baseUrl = null;

	/**
	 * Root-level single-origin signing secret.
	 */
	public ?string $secret = null;

	/**
	 * Root-level single-origin SVG setting.
	 */
	public bool $transformSvgs = false;

	/**
	 * Root-level single-origin animated GIF setting.
	 */
	public bool $transformAnimatedGifs = true;

	/**
	 * Map of origins.
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
	 * @var array<string, OriginConfig>
	 */
	public array $origins = [];

	/**
	 * Global default parameters for Small Pics transformations.
	 * These are applied in addition to any per origin defaults.
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
		if (! isset($values['origins']) || ! is_array($values['origins'])) {
			$values['origins'] = [];
		}

		$origins = $values['origins'];

		if ($origins === [] && ! empty($values['baseUrl'])) {
			$origins[self::DEFAULT_ORIGIN_NAME] = new OriginConfig([
				'baseUrl' => $values['baseUrl'],
				'secret' => $values['secret'] ?? null,
				'transformSvgs' => $values['transformSvgs'] ?? false,
				'transformAnimatedGifs' => $values['transformAnimatedGifs'] ?? false,
			]);
		}

		foreach ($origins as $key => $originConfig) {
			if (is_array($originConfig)) {
				$origins[(string) $key] = new OriginConfig($originConfig);
			}
		}

		if (! array_key_exists('defaultOrigin', $values) && $origins !== []) {
			$values['defaultOrigin'] = (string) array_key_first($origins);
		}

		$values['origins'] = $origins;

		parent::setAttributes($values, $safeOnly);
	}
}
