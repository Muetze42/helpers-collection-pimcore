<?php

namespace NormanHuth\HelpersPimcore;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pimcore\Db;
use Pimcore\Model\DataObject\Concrete;
use Doctrine\DBAL\Exception as DoctrineException;
use Pimcore\Tool;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pimcore as PimcoreLib;

class Pimcore
{
    /**
     * Get a Pimcore Kernel service container instance
     *
     * @param string $id
     * @param int    $invalidBehavior
     * @return object|null
     */
    public static function getContainer(
        string $id,
        int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
    ): ?object {
        return PimcoreLib::getKernel()->getContainer()->get($id, $invalidBehavior);
    }

    /**
     * Get all available and existing classes
     *
     * @throws DoctrineException
     */
    public static function getAvailableClasses(bool $returnClassDefinition = false): array
    {
        $db = Db::get();
        $classes = $db->fetchAllAssociative('SELECT * FROM classes');

        $objects = [];

        foreach ($classes as $class) {
            $className = $class['name'];
            $alias = '\Pimcore\Model\DataObject\\'.$className;
            if (file_exists(PIMCORE_CLASS_DEFINITION_DIRECTORY.'/DataObject/'.$className.'.php')
                && class_exists($alias)
            ) {
                /* @var Concrete $object */
                $object = new $alias();
                $objects[] = $returnClassDefinition ? $object->getClass() : $object;
            }
        }

        return $objects;
    }

    /**
     * Get available and valid languages
     *
     * @param string|null $locale
     * @throws Exception
     * @return array<string, string>
     */
    public static function getValidLanguages(?string $locale = null): array
    {
        if (!$locale) {
            $locale = Tool::getDefaultLanguage();
        }

        $validLanguages = Tool::getValidLanguages();
        $supportedLocales = Tool::getSupportedLocales();
        $languages = array_filter($supportedLocales, function ($value, $key) use ($validLanguages) {
            return in_array($key, $validLanguages);
        }, ARRAY_FILTER_USE_BOTH);

        if ($locale != 'en') {
            $base = explode('_', $locale)[0];

            if (defined('PIMCORE_COMPOSER_PATH')) {
                $projectPath = PIMCORE_COMPOSER_PATH;
            } else {
                $reflection = new ReflectionClass(self::class);
                $projectPath = dirname($reflection->getFileName(), 4);
            }

            $path = $projectPath.'/symfony/intl/Resources/data/languages/';
            $file = $path.$locale.'.php';

            if (!file_exists($file)) {
                $file = $path.$base.'.php';
            }

            if (file_exists($file)) {
                $translation = include $file;
                if (!empty($translation['Names'])) {
                    $languages = Arr::map($languages, function ($value, $key) use ($translation) {
                        return Str::ucfirst($translation['Names'][$key] ?? $value);
                    });
                }
            }
        }

        asort($languages);

        return $languages;
    }
}
