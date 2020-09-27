<?php

spl_autoload_register(function ($class) {

    $namespace = 'FluentValidator';

    if (substr($class, 0, strlen($namespace)) !== $namespace) {
        return;
    }

    $className = str_replace(
        array('\\', $namespace, strtolower($namespace)),
        array('/', 'src', ''),
        $class
    );

    $basePath = plugin_dir_path(__FILE__);

    $file = $basePath.trim($className, '/').'.php';

    if (is_readable($file)) {
        include $file;
    }
});
