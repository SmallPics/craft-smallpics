<?php
declare(strict_types = 1);

use fostercommerce\rector\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __FILE__,
	]);
