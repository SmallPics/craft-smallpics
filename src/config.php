<?php

return [
	// 'transformNativeImages' => true,
	// 'transformThumbnails' => true,
	// 'thumbnailParams' => [],
	// 'nativeTransformsParams' => [],
	// 'defaultOrigin' => 'default',
	'baseUrl' => getenv('SMALLPICS_BASE_URL'),
	'secret' => getenv('SMALLPICS_SECRET') ?: null,
	// 'transformSvgs' => false,
	// 'transformAnimatedGifs' => true,
	// 'origins' => [],
	// 'defaultParams' => [],
];
