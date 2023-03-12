<?php

namespace NormanHuth\HelpersPimcore;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use NormanHuth\Helpers\Exception\ClassNotFoundException;
use NormanHuth\HelpersPimcore\Support\Helper;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;

abstract class PimcoreModule
{
    /**
     * @param string|Asset|DataObject|Document|null $class
     * @return DataObject|Asset|Document|string
     */
    protected static function resolveClass(DataObject|Asset|Document|string|null $class): DataObject|Asset|Document|string
    {
        if ($class) {
            return $class;
        }

        return 'Pimcore\Model\\'.class_basename(get_called_class()).'\Folder';
    }

    /**
     * Get target folder or create if not exists and return folder object.
     *
     * @param string $targetPath
     * @param  null|DataObject\Folder|Asset\Folder|Document\Folder|string $class
     * @throws Exception
     * @return DataObject\Folder|Asset\Folder|Document\Folder|AbstractObject|Concrete
     */
    public static function getFolder(
        string $targetPath,
        #[ExpectedValues(values: [null, Asset\Folder::class, DataObject\Folder::class, Document\Folder::class])]
        DataObject\Folder|Asset\Folder|Document\Folder|string $class = null
    ): DataObject\Folder|Asset\Folder|Document\Folder|AbstractObject|Concrete {
        $targetPath = Helper::pathTrim($targetPath);

        $class = self::resolveClass($class);

        if (!class_exists($class)) {
            throw new ClassNotFoundException($class);
        }

        /* @var DataObject\Folder|Asset\Folder|Document\Folder $class */
        $target = $targetPath ? $class::getByPath($targetPath) : $class::getByPath('/');

        $folders = explode('/', $targetPath);

        if ($target) {
            return $target;
        }

        $path = '';

        foreach ($folders as $folder) {
            $parentId = $target ? $target->getId() : 1;
            $path = rtrim($path.'/'.$folder, '/\\');
            $target = $class::getByPath($path);
            if (!$target) {
                $target = new $class();
                $target->setKey(basename($path))
                    ->setParentId($parentId)
                    ->save();
            }
        }

        return $target;
    }
}
