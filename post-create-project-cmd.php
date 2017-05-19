<?php

namespace Schnittstabil\Boilerplate;

use \stdClass;
use \RecursiveIteratorIterator;
use \RecursiveCallbackFilterIterator;
use \RecursiveDirectoryIterator;

require __DIR__.'/vendor/autoload.php';

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ fileInfos ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$fileInfos = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator('.'),
        function ($fileInfo) {
            return !in_array($fileInfo->getFilename(), [
                '.',
                '..',
                'Cuzzy.php',
                'composer.lock',
                '.git',
                'vendor',
            ]);
        }
    )
);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ template  ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$cuzzy = new Cuzzy([
    'year' => date('Y'),
    'user_name' => trim(`git config user.name` ?: `whoami`),
    'user_email' => trim(`git config user.email`),
    'user_url' =>  trim(`git config user.url`),
    'user_link' => function ($cuzzy) {
        if (empty($cuzzy('{{user_url}}'))) {
            return $cuzzy('{{user_name}}');
        }

        return  $cuzzy('[{{user_name}}](http://{{user_url}})');
    },

    'project_name' => preg_replace('/-php$/', '', basename(__DIR__)),
    'class_name' => function ($cuzzy) {
        return str_replace('-', '', ucwords($cuzzy('{{project_name}}'), '-'));
    },
    'description' => '{{class_name}}',
    'create_description' => 'Create a {{class_name}}',
    'object_name' => function ($cuzzy) {
        return lcfirst($cuzzy('{{class_name}}'));
    },

    'package' => '{{vendor}}/{{project_name}}',
    'vendor' => function ($cuzzy) {
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($cuzzy('{{user_name}}'))));
    },
    'namespace' => function ($cuzzy) {
        $vendor = str_replace('-', '', ucwords($cuzzy('{{vendor}}'), '-'));
        return $vendor.'\\'.$cuzzy('{{class_name}}');
    },

    'sensio_labs_insight' => '[![SensioLabsInsight]({{sensio_labs_insight_url}}/big.png)]({{sensio_labs_insight_url}})',
    'sensio_labs_insight_url' => 'https://insight.sensiolabs.com/projects/{{your_project_id}}',

    'travis' => '[![Build Status]({{travis_url}}.svg?branch=master)]({{travis_url}})',
    'travis_url' => 'https://travis-ci.org/{{vendor}}/{{project_name}}',

    'coveralls' => '[![Coverage Status](https://coveralls.io/repos/{{vendor}}/{{project_name}}/badge.svg?branch=master&service=github)](https://coveralls.io/github/{{vendor}}/{{project_name}}?branch=master)',

    'codeclimate' => '[![Code Climate](https://codeclimate.com/github/{{vendor}}/{{project_name}}/badges/gpa.svg)](https://codeclimate.com/github/{{vendor}}/{{project_name}})',

    'scrutinizer' => '[![Scrutinizer Code Quality]({{scrutinizer_url}}/badges/quality-score.png?b=master)]({{scrutinizer_url}}/?branch=master)',
    'scrutinizer_url' => 'https://scrutinizer-ci.com/g/{{vendor}}/{{project_name}}',
]);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~ apply template ~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
foreach ($fileInfos as $fileInfo) {
    $newContent = $cuzzy(file_get_contents($fileInfo));
    file_put_contents($fileInfo, $newContent);
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ edit composer ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$composer = json_decode(file_get_contents('composer.json'));

unset($composer->scripts->{'post-create-project-cmd'});

$composer->name = $cuzzy('{{package}}');
$composer->description = $cuzzy('{{description}}');
$composer->keywords = array_values(array_diff($composer->keywords, ['boilerplate', 'skeleton', 'template']));
$composer->type = 'library';
$composer->authors[0]->name = $cuzzy('{{user_name}}');
$composer->authors[0]->email = $cuzzy('{{user_email}}');
$composer->autoload = new stdClass();
$composer->autoload->{'psr-4'} = new stdClass();
$composer->autoload->{'psr-4'}->{$cuzzy('{{namespace}}\\')} = 'src';
$composer->{'autoload-dev'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'}->{$cuzzy('{{namespace}}\\')} = 'tests';

file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ rename files ~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
foreach ([
    '.gitattributes.mustache' => '.gitattributes',
    'license.mustache' => 'license',
    'readme.mustache.md' => 'readme.md',
] as $from => $to) {
    unlink($to);
    rename($from, $to);
}

foreach ($fileInfos as $fileInfo) {
    $newPath = $cuzzy((string) $fileInfo);
    if ($newPath !== (string) $fileInfo) {
        rename($fileInfo, $newPath);
    }
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ clean up ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
unlink(__DIR__.'/Cuzzy.php');
unlink(__FILE__);
