<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use craft\base\Model;

class OriginConfig extends Model
{
	/**
	 * Base URL for the Small Pics origin.
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
	 * Origin default parameters for transform.
	 *
	 * These are applied in addition to any global defaults.
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
