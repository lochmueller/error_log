<?php

use HDNET\Autoloader\Utility\ArrayUtility;
use HDNET\Autoloader\Utility\ModelUtility;

// return (function () {

    $base = ModelUtility::getTcaInformation(\HDNET\ErrorLog\Domain\Model\Error::class);

    // custom manipulation calls here
    $custom = [];

    return ArrayUtility::mergeRecursiveDistinct($base, $custom);

// })();
