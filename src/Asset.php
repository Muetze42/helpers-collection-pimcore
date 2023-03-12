<?php

namespace NormanHuth\HelpersPimcore;

use Exception;
use NormanHuth\Helpers\Exception\FileNotFoundException;
use NormanHuth\HelpersPimcore\Support\Helper;
use Pimcore\Model\Asset as PimcoreAsset;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Asset extends PimcoreModule
{
    /**
     * Get a Pimcore asset resource or create if not exists.
     *
     * @param string|PimcoreAsset $asset
     * @param int|null            $userOwner
     * @param int|null            $userModification
     * @param string              $filename
     * @throws Exception
     * @return PimcoreAsset
     */
    public static function get(string|PimcoreAsset $asset, ?int $userOwner = 0, ?int $userModification = 0, string $filename = ''): PimcoreAsset
    {
        if (!$asset instanceof PimcoreAsset) {
            $path = $asset;
            $asset = PimcoreAsset::getByPath('/'.$path);

            if (!$asset) {
                $path = Helper::pathTrim($path);
                $target = Helper::splitPathFile($path);

                $folder = static::getFolder($target->path);
                $asset = (new PimcoreAsset())
                    ->setParentId($folder->getId())
                    ->setFilename($filename ?: $target->filename);

                if (!is_null($userOwner)) {
                    $asset->setUserOwner($userOwner);
                }
                if (!is_null($userOwner)) {
                    $asset->setUserOwner($userModification);
                }
            }
        }

        return $asset;
    }

    /**
     * Put exist file to assets.
     *
     * @param string|PimcoreAsset $asset
     * @param string $file
     * @param bool $deleteOriginalFile
     * @throws Exception
     * @return PimcoreAsset
     */
    public static function putFile(string|PimcoreAsset $asset, string $file, bool $deleteOriginalFile = false): PimcoreAsset
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException($file);
        }

        $contents = file_get_contents($file);

        $asset = self::get($asset);
        $asset->setData($contents);
        $asset->save();

        if ($deleteOriginalFile && file_exists($file)) {
            unlink($file);
        }

        return $asset;
    }

    /**
     * Store file contents to an Asset.
     *
     * @param string|PimcoreAsset $asset
     * @param mixed $contents
     * @throws Exception
     * @return PimcoreAsset
     */
    public static function put(string|PimcoreAsset $asset, mixed $contents): PimcoreAsset
    {
        $asset = self::get($asset);
        $asset->setData($contents);
        $asset->save();

        return $asset;
    }

    /**
     * Get the contents of an asset.
     *
     * @param string|PimcoreAsset|null $asset
     * @param bool $nullIfNotExists
     * @return string|null
     */
    public static function contents(string|PimcoreAsset|null $asset, bool $nullIfNotExists = false): ?string
    {
        if (is_null($asset) && $nullIfNotExists) {
            return null;
        }

        $path = static::path($asset);

        return File::contents($path, $nullIfNotExists);
    }

    /**
     * Get absolute server path of an asset.
     *
     * @param string|PimcoreAsset $path
     * @return string
     */
    public static function path(string|PimcoreAsset $path): string
    {
        $asset = $path instanceof PimcoreAsset ? $path : PimcoreAsset::getByPath($path);

        if ($asset) {
            $path = $path->getRealFullPath();
        }

        return PIMCORE_WEB_ROOT.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'assets'.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, '\\/') : '');
    }

    /**
     * Generate a response that forces the user's browser to stream the Asset file.
     *
     * @param string|PimcoreAsset $asset
     * @param string|null $name
     * @param array $headers
     * @return StreamedResponse
     */
    public static function stream(string|PimcoreAsset $asset, ?string $name = null, array $headers = []): StreamedResponse
    {
        $asset = static::path($asset);

        return File::stream($asset, $name, $headers);
    }

    /**
     * Generate a response that forces the user's browser to download the Asset file at the given path.
     *
     * @param string|PimcoreAsset $asset
     * @param string|null $name
     * @param array $headers
     * @return StreamedResponse
     */
    public static function download(string|PimcoreAsset $asset, ?string $name = null, array $headers = []): StreamedResponse
    {
        $asset = static::path($asset);

        return File::download($asset, $name, $headers);
    }

    /**
     * Append to an asset
     *
     * @param string|PimcoreAsset|null $asset
     * @param string $data
     * @param bool $nullIfNotExists
     * @param string $separator
     * @throws Exception
     * @return PimcoreAsset
     */
    public static function append(
        string|PimcoreAsset|null $asset,
        string $data,
        bool $nullIfNotExists = true,
        string $separator = PHP_EOL
    ): PimcoreAsset {
        $asset = self::get($asset);
        $contents = self::contents($asset, $nullIfNotExists);
        if (!$contents) {
            $contents = '';
        }

        return self::put($asset, trim($contents.$separator.$data));
    }
}
