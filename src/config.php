<?php

declare(strict_types=1);

return [
	// 'transformNativeImages' => true,
	// 'transformThumbnails' => true,
	// 'thumbnailParams' => [],
	// 'nativeTransformsParams' => [],
	// 'excludedVolumes' => [],
	// 'defaultSource' => 'default',
	'baseUrl' => getenv('SMALLPICS_BASE_URL'),
	'secret' => getenv('SMALLPICS_SECRET') ?: null,
	// 'transformSvgs' => false,
	// 'transformAnimatedGifs' => true,
	// 'sources' => [],
	// 'defaultParams' => [],
];
