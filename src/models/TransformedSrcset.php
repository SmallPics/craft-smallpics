<?php

declare(strict_types=1);

namespace smallpics\craft\models;

use ArrayAccess;
use IteratorAggregate;
use LogicException;
use Stringable;
use Traversable;

/**
 * @implements ArrayAccess<string, TransformedImage>
 * @implements IteratorAggregate<string, TransformedImage>
 */
class TransformedSrcset implements ArrayAccess, IteratorAggregate, Stringable
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

	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists((string) $offset, $this->images);
	}

	public function offsetGet(mixed $offset): ?TransformedImage
	{
		return $this->images[(string) $offset] ?? null;
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		throw new LogicException(self::class . ' is read-only.');
	}

	public function offsetUnset(mixed $offset): void
	{
		throw new LogicException(self::class . ' is read-only.');
	}

	/**
	 * @return Traversable<string, TransformedImage>
	 */
	public function getIterator(): Traversable
	{
		yield from $this->images;
	}

	/**
	 * @return array<string, TransformedImage>
	 */
	public function getImages(): array
	{
		return $this->images;
	}
}
