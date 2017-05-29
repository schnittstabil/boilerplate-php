<?php

namespace Schnittstabil\Boilerplate;

use \stdClass;
use \RecursiveIteratorIterator;
use \RecursiveCallbackFilterIterator;
use \RecursiveDirectoryIterator;
use function Schnittstabil\curty;

require_once __DIR__.'/curty.phar';

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ fileInfos ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$fileInfos = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator('.'),
        function ($fileInfo) {
            return !in_array($fileInfo->getFilename(), [
                '.',
                '..',
                'curty.phar',
                'composer.lock',
                '.git',
                'vendor',
            ]);
        }
    )
);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ template  ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$ctx = new stdClass();
$ctx->year = date('Y');
$ctx->user_name = trim(`git config user.name` ?: `whoami`);
$ctx->user_email = trim(`git config user.email`);
$ctx->user_url =  trim(`git config user.url`);
$ctx->user_link = function ($ctx) {
    if (empty(curty('{{user_url}}', $ctx))) {
        return curty('{{user_name}}', $ctx);
    }

    return  curty('[{{user_name}}](http://{{user_url}})', $ctx);
};

$ctx->project_name = preg_replace('/-php$/', '', basename(__DIR__));
$ctx->class_name = function ($ctx) {
        return str_replace('-', '', ucwords(curty('{{project_name}}', $ctx), '-'));
    };
$ctx->description = '{{class_name}}';
$ctx->create_description = 'Create a {{class_name}}';
$ctx->object_name = function ($ctx) {
    return lcfirst(curty('{{class_name}}', $ctx));
};

$ctx->package = '{{vendor}}/{{project_name}}';
$ctx->vendor = function ($ctx) {
    return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim(curty('{{user_name}}', $ctx))));
};
$ctx->namespace = function ($ctx) {
    $vendor = str_replace('-', '', ucwords(curty('{{vendor}}', $ctx), '-'));
    return $vendor.'\\'.curty('{{class_name}}', $ctx);
};

$ctx->sensio_labs_insight = '[![SensioLabsInsight]({{sensio_labs_insight_url}}/big.png)]({{sensio_labs_insight_url}})';
$ctx->sensio_labs_insight_url = 'https://insight.sensiolabs.com/projects/{{your_project_id}}';

$ctx->travis = '[![Build Status]({{travis_url}}.svg?branch=master)]({{travis_url}})';
$ctx->travis_url = 'https://travis-ci.org/{{vendor}}/{{project_name}}';

$ctx->coveralls = '[![Coverage Status](https://coveralls.io/repos/{{vendor}}/{{project_name}}/badge.svg?branch=master&service=github)](https://coveralls.io/github/{{vendor}}/{{project_name}}?branch=master)';

$ctx->codeclimate = '[![Code Climate](https://codeclimate.com/github/{{vendor}}/{{project_name}}/badges/gpa.svg)](https://codeclimate.com/github/{{vendor}}/{{project_name}})';

$ctx->scrutinizer = '[![Scrutinizer Code Quality]({{scrutinizer_url}}/badges/quality-score.png?b=master)]({{scrutinizer_url}}/?branch=master)';
$ctx->scrutinizer_url = 'https://scrutinizer-ci.com/g/{{vendor}}/{{project_name}}';

$ctx = array_merge((array) $ctx, $_SERVER);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~ apply template ~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
foreach ($fileInfos as $fileInfo) {
    $newContent = curty(file_get_contents($fileInfo), $ctx);
    file_put_contents($fileInfo, $newContent);
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ edit composer ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$composer = json_decode(file_get_contents('composer.json'));

unset($composer->scripts->{'post-create-project-cmd'});
unset($composer->scripts->{'install-curty'});

$composer->name = curty('{{package}}', $ctx);
$composer->description = curty('{{description}}', $ctx);
$composer->keywords = array_values(array_diff($composer->keywords, ['boilerplate', 'skeleton', 'template']));
$composer->type = 'library';
$composer->authors[0]->name = curty('{{user_name}}', $ctx);
$composer->authors[0]->email = curty('{{user_email}}', $ctx);
$composer->autoload = new stdClass();
$composer->autoload->{'psr-4'} = new stdClass();
$composer->autoload->{'psr-4'}->{curty('{{namespace}}\\', $ctx)} = 'src';
$composer->{'autoload-dev'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'} = new stdClass();
$composer->{'autoload-dev'}->{'psr-4'}->{curty('{{namespace}}\\', $ctx)} = 'tests';

file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ rename files ~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
foreach ([
    '.gitattributes.mustache' => '.gitattributes',
    'appveyor.mustache.yml' => 'appveyor.yml',
    'license.mustache' => 'license',
    'readme.mustache.md' => 'readme.md',
] as $from => $to) {
    if (file_exists($to)) {
        unlink($to);
    }
    rename($from, $to);
}

foreach ($fileInfos as $fileInfo) {
    $newPath = curty((string) $fileInfo, $ctx);
    if ($newPath !== (string) $fileInfo) {
        rename($fileInfo, $newPath);
    }
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ clean up ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
unlink(__DIR__.'/curty.phar');
unlink(__FILE__);
