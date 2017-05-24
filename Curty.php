<?php

namespace Schnittstabil\Boilerplate;

use Closure;

/**
 * Curty micro templating.
 */
class Curty
{
    /**
     * @var array<string, Closure|mixed> variable mapping
     */
    protected $context = [];

    /**
     * Create a Curty template renderer.
     *
     * @param array<string, Closure|mixed> $context variable mapping
     */
    public function __construct(array $context = array())
    {
        $this->context = $context;
    }

    /**
     * Render template until a fixed point is reached.
     *
     * @param string $tpl Curty template
     *
     * @return string
     */
    public function __invoke(string $tpl) : string
    {
        do {
            $old = $tpl;
            $tpl = $this->render($tpl);
        } while ($tpl !== $old);

        return $tpl;
    }

    /**
     * Render template.
     *
     * @param string $tpl Curty template
     *
     * @return string
     */
    public function render(string $tpl) : string
    {
        if (!preg_match_all('/{{([^{]+?)}}/', $tpl, $matches)) {
            return $tpl;
        }

        foreach ($matches[1] as $key) {
            if ($this->hasValue($key)) {
                $tpl = str_replace('{{'.$key.'}}', $this->getValue($key), $tpl);
            }
        }

        return $tpl;
    }

    protected function hasValue(string $key) : bool
    {
        return array_key_exists($key, $this->context);
    }

    protected function getValue(string $key) : string
    {
        $value = $this->context[$key];

        if ($value instanceof Closure) {
            return $value($this);
        }

        if (is_bool($value)) {
            return var_export($value, true);
        }

        if (is_scalar($value) || method_exists($value, '__toString')) {
            return (string) $value;
        }

        return @json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}
