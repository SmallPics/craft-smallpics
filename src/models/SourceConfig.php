<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use craft\base\Model;

class SourceConfig extends Model
{
	/**
	 * Base URL for the Small Pics source.
	 */
	public ?string $baseUrl = null;

	/**
	 * Signing secret for the URL.
	 */
	public ?string $secret = null;

	/**
	 * Whether SVGs should be transformed.
	 */
	public bool $transformSvgs = false;

	/**
	 * Whether animated GIFs should be transformed.
	 */
	public bool $transformAnimatedGifs = true;

	/**
	 * Source default parameters for transforms.
	 *
	 * These are applied in addition to global defaults.
	 *
	 * @var array<string, mixed>
	 */
	public array $defaultParams = [];

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (! isset($config['defaultParams'])) {
			$config['defaultParams'] = [];
		}

		parent::__construct($config);
	}
}
