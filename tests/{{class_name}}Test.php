<?php

namespace {{namespace}};

class {{class_name}}Test extends \PHPUnit\Framework\TestCase
{
    public function test{{class_name}}ShouldBeInstanceOf{{class_name}}()
    {
        $this->assertInstanceOf(
            {{class_name}}::class,
            new {{class_name}}()
        );
    }
}
