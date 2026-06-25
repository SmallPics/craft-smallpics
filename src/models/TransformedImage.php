<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use craft\elements\Asset;
use smallpics\smallpics\enums\Format;
use smallpics\smallpics\Options;
use Stringable;

class TransformedImage implements Stringable
{
	public const DEFAULT_MIME_TYPE = 'application/octet-stream';

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
			return $this->asset->getMimeType() ?? self::DEFAULT_MIME_TYPE;
		}

		return [
			'jpg' => 'image/jpeg',
			'pjpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
		][$format->value];
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
