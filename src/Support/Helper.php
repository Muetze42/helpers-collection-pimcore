<?php

namespace NormanHuth\HelpersPimcore\Support;

/**
 * @internal
 */
class Helper
{
    /**
     * Strip directory characters from the beginning and end of a string
     *
     * @param string $path
     * @return string
     * @internal
     */
    public static function pathTrim(string $path): string
    {
        return str_replace('\\', '/', trim($path, '/\\'));
    }

    /**
     * Split path and file from each other
     *
     * @param string $path
     * @return object
     * @internal
     */
    public static function splitPathFile(string $path): object
    {
        $parts = explode('/', $path);
        $filename = end($parts);
        unset($parts[array_key_last($parts)]);
        $path = implode('/', $parts);

        return (object) [
            'filename' => $filename,
            'path'     => $path,
        ];
    }
}
