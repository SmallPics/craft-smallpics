<?php

declare(strict_types=1);

namespace smallpics\craft\assetpreviews;

use craft\assetpreviews\Image as CraftImage;
use smallpics\craft\Plugin;

class Image extends CraftImage
{
	private const MAX_SIZE = 2560;

	/**
	 * @param array<string, mixed> $variables
	 */
	public function getPreviewHtml(array $variables = []): string
	{
		$url = Plugin::$instance->transformer->transformImage($this->asset, [
			'width' => min((int) $this->asset->getWidth(), self::MAX_SIZE),
			'height' => min((int) $this->asset->getHeight(), self::MAX_SIZE),
			'mode' => 'fit',
			...Plugin::settings()->thumbnailParams,
		]);

		return parent::getPreviewHtml([
			'url' => (string) $url,
			...$variables,
		]);
	}
}
