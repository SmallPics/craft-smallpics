<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use craft\elements\Asset;
use smallpics\smallpics\enums\Format;
use smallpics\smallpics\Options;
use Stringable;

class TransformedImage implements Stringable
{
	public const DEFAULT_MIME_TYPE = 'image/avif';

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(
		private readonly string $url,
		private readonly Asset $asset,
		private readonly Options $options,
		private readonly array $config = [],
	) {
	}

	public function __toString(): string
	{
		return $this->getUrl();
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getWidth(): int
	{
		return $this->options->getWidth() ?? 0;
	}

	public function getHeight(): int
	{
		return $this->options->getHeight() ?? 0;
	}

	public function getMimeType(): string
	{
		$format = $this->options->getFormat();

		if (! $format instanceof Format) {
			// Small Pics transformer will also use the Accept header to determine a response format, but
			// this plugin will never see what the actual response format is, so this is based on the Small
			// Pics default response format.
			return self::DEFAULT_MIME_TYPE;
		}

		return match ($format->value) {
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'jxl' => 'image/jxl',
			default => self::DEFAULT_MIME_TYPE,
		};
	}

	public function getSource(): Asset
	{
		return $this->asset;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getConfig(): array
	{
		return $this->config;
	}

	public function getOptions(): Options
	{
		return $this->options;
	}
}
