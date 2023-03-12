<?php

namespace NormanHuth\HelpersPimcore;

use Illuminate\Support\Str;
use JetBrains\PhpStorm\ExpectedValues;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Component\HttpFoundation\StreamedResponse;

class File
{
    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     * @return string|false
     */
    public static function mimeType(string $path): bool|string
    {
        return (new ExtensionMimeTypeDetector())->detectMimeTypeFromPath($path);
    }
    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param string $name
     * @return string
     */
    protected static function fallbackName(string $name): string
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Return Symfony StreamedResponse
     *
     * @param string      $path
     * @param string|null $name
     * @param array       $headers
     * @param string      $disposition
     * @return StreamedResponse
     */
    public static function response(
        string $path,
        ?string $name = null,
        array $headers = [],
        #[ExpectedValues(values: ['inline', 'attachment', 'extension-token'])]
        string $disposition = 'inline'
    ): StreamedResponse {
        $response = new StreamedResponse();

        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = static::mimeType($path);
        }

        if (!array_key_exists('Content-Length', $headers)) {
            $headers['Content-Length'] = static::size($path);
        }

        if (!array_key_exists('Content-Disposition', $headers)) {
            $filename = $name ?? basename($path);

            $disposition = $response->headers->makeDisposition(
                $disposition,
                $filename,
                static::fallbackName($filename)
            );

            $headers['Content-Disposition'] = $disposition;
        }

        $response->headers->replace($headers);

        $response->setCallback(function () use ($path) {
            $stream = fopen($path, 'r');
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

    /**
     * Download file a given file
     *
     * @param string      $path
     * @param string|null $name
     * @param array       $headers
     * @return StreamedResponse
     */
    public static function download(string $path, ?string $name = null, array $headers = []): StreamedResponse
    {
        return static::response($path, $name, $headers, 'attachment');
    }

    /**
     * Stream file a given file
     *
     * @param string $path
     * @param string|null $name
     * @param array $headers
     * @return StreamedResponse
     */
    public static function stream(string $path, ?string $name = null, array $headers = []): StreamedResponse
    {
        return static::response($path, $name, $headers);
    }

    /**
     * Get filesize of a given file
     *
     * @param string $path
     * @return int
     */
    public static function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get absolute server path of a file
     *
     * @param string $path
     * @return string
     */
    public static function path(string $path): string
    {
        return file_exists($path) ? $path : base_path($path);
    }

    /**
     * Get the contents of a file
     *
     * @param string $file
     * @param bool $nullIfNotExists
     * @return string|null
     */
    public static function contents(string $file, bool $nullIfNotExists = false): ?string
    {
        $file = self::path($file);

        if ($nullIfNotExists && !file_exists($file)) {
            return null;
        }

        $size = File::size($file);

        if ($size > 0) {
            clearstatcache(true, $file);
            $handle = fopen($file, 'r');
            $contents = fread($handle, $size);
            fclose($handle);

            return $contents;
        }

        return '';
    }

    /**
     * Put content to file
     *
     * @param string $file
     * @param mixed  $contents
     * @return int|false
     */
    public static function put(string $file, mixed $contents): int|false
    {
        return file_put_contents($file, $contents);
    }

    /**
     * Put content to file
     *
     * @param string $file
     * @param mixed  $contents
     * @return int|false
     */
    public static function putFile(string $file, mixed $contents): int|false
    {
        $contents = file_get_contents($contents);

        return file_put_contents($file, $contents);
    }

    /**
     * Append data to a file
     *
     * @param string $file
     * @param string $data
     * @param bool $nullIfNotExists
     * @param string $separator
     * @return bool|int
     */
    public static function append(string $file, string $data, bool $nullIfNotExists = true, string $separator = PHP_EOL): bool|int
    {
        $contents = self::contents($file, $nullIfNotExists);
        if (!$contents) {
            $contents = '';
        }

        return self::put($file, trim($contents.$separator.$data));
    }

    /**
     * Prepend data to a file
     *
     * @param string $file
     * @param string $data
     * @param bool $nullIfNotExists
     * @param string $separator
     * @return bool|int
     */
    public static function prepend(string $file, string $data, bool $nullIfNotExists = true, string $separator = PHP_EOL): bool|int
    {
        $contents = self::contents($file, $nullIfNotExists);
        if (!$contents) {
            $contents = '';
        }

        return self::put($file, trim($data.$separator.$contents));
    }
}
