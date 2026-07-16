<?php

declare(strict_types=1);

namespace smallpics\craft\variables;

use craft\elements\Asset;
use craft\models\ImageTransform;
use smallpics\craft\models\TransformedImage;
use smallpics\craft\models\TransformedSrcset;
use smallpics\craft\Plugin;

class SmallPicsVariable
{
	/**
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function transformImage(Asset $image, array|string|ImageTransform $config = []): TransformedImage
	{
		return Plugin::$instance->transformer->transformImage($image, $config);
	}

	/**
	 * @param array<string, array<string, mixed>> $descriptors
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function srcset(Asset $image, array $descriptors, array|string|ImageTransform $config = []): TransformedSrcset
	{
		return Plugin::$instance->transformer->srcset($image, $descriptors, $config);
	}
}
