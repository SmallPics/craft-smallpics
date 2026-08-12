<?php

return [
	// 'transformNativeImages' => true,
	// 'transformThumbnails' => true,
	// 'thumbnailParams' => [],
	// 'nativeTransformsParams' => [],
	// 'defaultSource' => 'default',
	'baseUrl' => getenv('SMALLPICS_BASE_URL'),
	'secret' => getenv('SMALLPICS_SECRET') ?: null,
	// 'transformSvgs' => false,
	// 'transformAnimatedGifs' => true,
	// 'sources' => [],
	// 'defaultParams' => [],
];
