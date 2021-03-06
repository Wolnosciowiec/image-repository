<?php declare(strict_types=1);

namespace Helper;

trait TemplatingTrait
{
    public function fill(string $url, array $vars): string
    {
        foreach ($vars as $var => $value) {
            $url = str_replace(
                ['{{' . $var . '}}', '{{ ' . $var . ' }}'],
                $value,
                $url
            );
        }

        return $url;
    }
}
