#!/usr/bin/env php
<?php

namespace Usage;

require __DIR__.'/../vendor/autoload.php';

ob_start();

// *************************** readme-usage start ***************************

use {{namespace}}\{{class_name}};

// {{create_description}}
${{object_name}} = new {{class_name}}();

echo get_class(${{object_name}});

// **************************** readme-usage end ****************************

$actual = ob_get_clean();

$expected = '{{namespace}}\{{class_name}}';

var_dump([
    'actual' => $actual,
    'expected' => $expected,
]);

exit($actual === $expected ? 0 : 1);
