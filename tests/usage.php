#!/usr/bin/env php
<?php

namespace Usage;

require __DIR__.'/../vendor/autoload.php';

use {{namespace}}\{{class_name}};

// {{create_description}}
${{object_name}} = new {{class_name}}();

exit(${{object_name}} instanceof {{class_name}} === true ? 0 : 1);
