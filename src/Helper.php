<?php

namespace smallpics\craft;

use craft\elements\Asset;
use Throwable;

class Helper
{
	public static function isSvg(Asset $image): bool
	{
		return self::extension($image) === 'svg';
	}

	public static function isAnimatedGif(Asset $image): bool
	{
		if (self::extension($image) !== 'gif') {
			return false;
		}

		$stream = null;

		try {
			$stream = $image->getStream();
			$count = 0;
			$matches = [];

			while (! feof($stream) && $count < 2) {
				$chunk = fread($stream, 1024 * 100);

				if ($chunk === false) {
					break;
				}

				$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
			}

			return $count > 1;
		} catch (Throwable) {
			return false;
		} finally {
			if (is_resource($stream)) {
				fclose($stream);
			}
		}
	}

	private static function extension(Asset $image): string
	{
		return strtolower($image->extension);
	}
}
