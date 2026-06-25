<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use Stringable;

class TransformedSrcset implements Stringable
{
	/**
	 * @param array<string, TransformedImage> $images
	 */
	public function __construct(
		private readonly array $images
	) {
	}

	public function __toString(): string
	{
		$parts = [];

		foreach ($this->images as $descriptor => $image) {
			$parts[] = trim($image->getUrl() . ' ' . $descriptor);
		}

		return implode(', ', $parts);
	}

	/**
	 * @return array<string, TransformedImage>
	 */
	public function getImages(): array
	{
		return $this->images;
	}
}
