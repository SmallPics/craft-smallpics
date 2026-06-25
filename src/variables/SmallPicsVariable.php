<?php

declare(strict_types=1);

namespace smallpics\craft\variables;

use craft\elements\Asset;
use craft\models\ImageTransform;
use smallpics\craft\models\TransformedImage;
use smallpics\craft\models\TransformedSrcset;
use smallpics\craft\Plugin;
use yii\base\InvalidConfigException;

class SmallPicsVariable
{
	/**
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function transformImage(Asset $image, array|string|ImageTransform $config = []): TransformedImage
	{
		return $this->plugin()->getTransformer()->transformImage($image, $config);
	}

	/**
	 * @param array<string, array<string, mixed>> $descriptors
	 * @param array<string, mixed>|string|ImageTransform $config
	 */
	public function srcset(Asset $image, array $descriptors, array|string|ImageTransform $config = []): TransformedSrcset
	{
		return $this->plugin()->getTransformer()->srcset($image, $descriptors, $config);
	}

	private function plugin(): Plugin
	{
		$plugin = Plugin::getInstance();

		if (! $plugin instanceof Plugin) {
			throw new InvalidConfigException('Small Pics plugin is not available.');
		}

		return $plugin;
	}
}
