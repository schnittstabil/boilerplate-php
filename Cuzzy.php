<?php

namespace Schnittstabil\Boilerplate;

class Cuzzy
{
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function get(string $id)
    {
        $value = getenv($id);

        if ($value !== false) {
            return $value;
        }

        if (!array_key_exists($id, $this->data)) {
            return $id;
        }

        $value = $this->data[$id];

        return is_callable($value) ? $value($this) : $value;
    }

    function __invoke(string $content)
    {
        do {
            $old = $content;

            if (!preg_match_all('/{{([^{]+?)}}/', $content, $m)) {
                return $content;
            }

            foreach($m[1] as $id) {
                $content = str_replace('{{'.$id.'}}', $this->get($id), $content);
            }
        } while ($content !== $old);

        return $content;
    }
}
