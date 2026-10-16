<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use craft\elements\Asset;
use craft\models\ImageTransform;
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
		private readonly bool $sourcePassthrough = false,
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
		return $this->dimension($this->options->getParam(Options::WIDTH), 'width');
	}

	public function getHeight(): int
	{
		return $this->dimension($this->options->getParam(Options::HEIGHT), 'height');
	}

	public function getMimeType(): string
	{
		if (
			$this->options->getPassthrough()
			&& strtolower($this->asset->getExtension()) === 'svg'
		) {
			return 'image/svg+xml';
		}

		$format = $this->sourcePassthrough
			? strtolower($this->asset->getExtension())
			: $this->options->getFormat()?->value;

		return match ($format) {
			'jpg', 'pjpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'jxl' => 'image/jxl',
			'avif' => 'image/avif',
			default => '',
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

	private function dimension(int|float|string|null $value, string $axis): int
	{
		if (
			is_string($value)
			&& preg_match('/^([\d.]+)([pwh])$/', $value, $matches)
		) {
			$width = $matches[2] === 'w'
				|| ($matches[2] === 'p' && $axis === 'width');

			$sourceTransform = new ImageTransform([
				'upscale' => false,
			]);

			$base = $width
				? $this->asset->getWidth($sourceTransform)
				: $this->asset->getHeight($sourceTransform);

			return (int) round((float) $matches[1] * ($base ?? 0) / 100);
		}

		return (int) ($value ?? 0);
	}
}
