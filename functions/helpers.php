<?php

use Symfony\Contracts\Translation\TranslatorInterface;
use NormanHuth\HelpersPimcore\Pimcore;

if (!function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param string      $id
     * @param array       $parameters
     * @param string|null $locale
     * @param string|null $domain
     * @return string
     */
    function __(string $id, array $parameters = [], string $locale = null, string $domain = null): string
    {
        $translator = Pimcore::getContainer(TranslatorInterface::class);

        return $translator->trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('log_path')) {
    /**
     * Get the path of the Pimcore log directory.
     *
     * @param string $path
     * @return string
     */
    function log_path(string $path = ''): string
    {
        return PIMCORE_LOG_DIRECTORY.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, '\\/') : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, '\\/') : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public web root folder.
     *
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return PIMCORE_WEB_ROOT.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, '\\/') : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param string $path
     * @param string $baseDirectory
     * @return string
     */
    function app_path(string $path = '', string $baseDirectory = 'src'): string
    {
        return PIMCORE_PROJECT_ROOT.DIRECTORY_SEPARATOR.$baseDirectory.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, '\\/') : '');
    }
}
