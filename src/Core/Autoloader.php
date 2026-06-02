<?php

declare(strict_types=1);

namespace CentralDeAulas\Core;

final class Autoloader
{
    public static function register(string $baseDirectory, string $baseNamespace): void
    {
        spl_autoload_register(
            static function (string $className) use ($baseDirectory, $baseNamespace): void {
                if (strpos($className, $baseNamespace) !== 0) {
                    return;
                }

                $relativeClass = substr($className, strlen($baseNamespace));
                $file = rtrim($baseDirectory, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                    . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            }
        );
    }
}

