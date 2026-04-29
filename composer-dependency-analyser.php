<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration;

return $config
    ->addPathRegexToExclude('~Test(Cases)?\.php$~')
    ->ignoreErrorsOnPackage('aryeo/tooling-laravel', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('nikic/php-parser', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('phpstan/phpstan', [ErrorType::SHADOW_DEPENDENCY]);
