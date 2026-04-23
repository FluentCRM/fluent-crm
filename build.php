<?php
// set only to run from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line!');
}

// get args from command line
$nodeBuild = in_array('--node-build', $argv);

if ($nodeBuild) {

    echo "\nBuilding Main App\n";
    $ret = exec("npx mix --production", $out3, $err4);
    if ($err4) {
        print_r($err4);
    }
    echo implode("\n", $out3);
}

define('WP_USE_THEMES', false);
require(__DIR__ . '/../../../wp-blog-header.php');


$fileLists = [
    'composer.json',
    'fluent-crm.php',
    'index.php',
    'readme.txt',
];
$renameFiles = [];
$deleteFolders = [];
$deleteFiles = [];
$targetFolder = 'builds/fluent-crm';


$folderLists = [
    'app',
    'assets',
    'boot',
    'config',
    'database',
    'includes',
    'language',
    'vendor',
];

global $wp_filesystem;
require_once(ABSPATH . '/wp-admin/includes/file.php');
WP_Filesystem();

function deleteFileOrFolder($fileOrFolder)
{
    echo 'Deleting: ' . $fileOrFolder;
    // new line
    echo "\n";
    $fileSystemDirect = new WP_Filesystem_Direct(false);
    $result = $fileSystemDirect->rmdir($fileOrFolder, true);

    if (!$result) {
        echo 'ERROR on Delete: ' . $fileOrFolder;
    }
}

function copyFileOrFolder($src, $dest)
{
    if (is_file($src)) {
        copy($src, $dest);
        return;
    }

    $result = copy_dir($src, $dest);

    if (is_wp_error($result)) {
        echo 'ERROR: ' . $result->get_error_message();
    }
}

// delete the folder if exists
if (file_exists($targetFolder)) {
    deleteFileOrFolder($targetFolder);
}

if (!file_exists($targetFolder)) {
    mkdir($targetFolder, 0755, true);
}

foreach ($fileLists as $file) {
    $source = __DIR__ . '/' . $file;
    $target = $targetFolder . '/' . $file;
    copyFileOrFolder($source, $target);
}

foreach ($folderLists as $folder) {
    $source = __DIR__ . '/' . $folder;
    $target = $targetFolder . '/' . $folder;
    echo $target . "\n";
    copyFileOrFolder($source, $target);
}

foreach ($deleteFolders as $folder) {
    $target = $targetFolder . '/' . $folder;
    deleteFileOrFolder($target);
}

// delete the $deleteFiles files now
foreach ($deleteFiles as $file) {
    $target = $targetFolder . '/' . $file;
    deleteFileOrFolder($target);
}

// Rename Required Files
foreach ($renameFiles as $source => $target) {
    rename($targetFolder . '/' . $source, $targetFolder . '/' . $target);
}

echo "\nBuild Completed";

//@ob_start();
//
//$shell = system("tput cols");
//
//@ob_end_clean();
echo "\nNow Compressing the Build as fluent-crm.zip\n";
$rootPath = realpath('builds');

// Remove any trailing slashes from the path
$rootPath = rtrim($rootPath, '\\/');

// Get real path for our folder
$rootPath = realpath('builds/fluent-crm');

// Initialize archive object
$zip = new ZipArchive();
$zip->open('builds/fluent-crm.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file)
{
    // Skip directories (they would be added automatically)
    if (!$file->isDir())
    {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
    echo "\e[0;32m█\e[0m";
    usleep(1000);
}
// Zip archive will be created only after closing object
$zip->close();

echo "\nCompressing Completed. Check the build directory\n";
return;
