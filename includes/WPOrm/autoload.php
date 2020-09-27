<?php

spl_autoload_register(function ($class) {

    $namespace = 'WPManageNinja\WPOrm';

    if (substr($class, 0, strlen($namespace)) !== $namespace) {
        return;
    }

    $className = str_replace(
        '\\', '/', str_replace($namespace, 'src', $class)
    );

    $basePath = plugin_dir_path(__FILE__);

    $file = $basePath.trim($className, '/').'.php';

    is_readable($file) && include $file;
});
