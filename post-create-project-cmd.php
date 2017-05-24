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
                'Curty.php',
                'composer.lock',
                '.git',
                'vendor',
            ]);
        }
    )
);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ template  ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
class EnvCurty extends Curty
{
    protected function hasValue(string $key) : bool
    {
        return getenv($key) !== false || parent::hasValue($key);
    }

    protected function getValue(string $key) : string
    {
        $envValue = getenv($key);

        if ($envValue !== false) {
            return $envValue;
        }

        return parent::getValue($key);
    }
}

$curty = new EnvCurty([
    'year' => date('Y'),
    'user_name' => trim(`git config user.name` ?: `whoami`),
    'user_email' => trim(`git config user.email`),
    'user_url' =>  trim(`git config user.url`),
    'user_link' => function ($curty) {
        if (empty($curty('{{user_url}}'))) {
            return $curty('{{user_name}}');
        }

        return  $curty('[{{user_name}}](http://{{user_url}})');
    },

    'project_name' => preg_replace('/-php$/', '', basename(__DIR__)),
    'class_name' => function ($curty) {
        return str_replace('-', '', ucwords($curty('{{project_name}}'), '-'));
    },
    'description' => '{{class_name}}',
    'create_description' => 'Create a {{class_name}}',
    'object_name' => function ($curty) {
        return lcfirst($curty('{{class_name}}'));
    },

    'package' => '{{vendor}}/{{project_name}}',
    'vendor' => function ($curty) {
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($curty('{{user_name}}'))));
    },
    'namespace' => function ($curty) {
        $vendor = str_replace('-', '', ucwords($curty('{{vendor}}'), '-'));
        return $vendor.'\\'.$curty('{{class_name}}');
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
    $newContent = $curty(file_get_contents($fileInfo));
    file_put_contents($fileInfo, $newContent);
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ edit composer ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$composer = json_decode(file_get_contents('composer.json'));

unset($composer->scripts->{'post-create-project-cmd'});

$composer->name = $curty('{{package}}');
$composer->description = $curty('{{description}}');
$composer->keywords = array_values(array_diff($composer->keywords, ['boilerplate', 'skeleton', 'template']));
$composer->type = 'library';
$composer->authors[0]->name = $curty('{{user_name}}');
$composer->authors[0]->email = $curty('{{user_email}}');
$composer->autoload = new stdClass();
$composer->autoload->{'psr-4'} = new stdClass();
$composer->autoload->{'psr-4'}->{$curty('{{namespace}}\\')} = 'src';
$composer->{'autoload-dev'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'}->{$curty('{{namespace}}\\')} = 'tests';

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
    $newPath = $curty((string) $fileInfo);
    if ($newPath !== (string) $fileInfo) {
        rename($fileInfo, $newPath);
    }
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ clean up ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
unlink(__DIR__.'/Curty.php');
unlink(__FILE__);
