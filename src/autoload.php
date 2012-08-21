<?php

$loader = require BOX_PATH . '/src/vendors/autoload.php';

// see: https://github.com/kherge/Box/issues/27
if ($phar = \Phar::running()) {
    $phar .= '/src/vendors/symfony/finder/Symfony/Component/Finder';
    $class = 'Symfony\\Component\\Finder';
    $load = array(
        "$class\\Comparator\\Comparator" => "$phar/Comparator/Comparator.php",
        "$class\\Comparator\\DateComparator" => "$phar/Comparator/DateComparator.php",
        "$class\\Comparator\\NumberComparator" => "$phar/Comparator/NumberComparator.php",
        "$class\\Iterator\\CustomFilterIterator" => "$phar/Iterator/CustomFilterIterator.php",
        "$class\\Iterator\\DateRangeFilterIterator" => "$phar/Iterator/DateRangeFilterIterator.php",
        "$class\\Iterator\\DepthRangeFilterIterator" => "$phar/Iterator/DepthRangeFilterIterator.php",
        "$class\\Iterator\\ExcludeDirectoryFilterIterator" => "$phar/Iterator/ExcludeDirectoryFilterIterator.php",
        "$class\\Iterator\\FilecontentFilterIterator" => "$phar/Iterator/FilecontentFilterIterator.php",
        "$class\\Iterator\\FilenameFilterIterator" => "$phar/Iterator/FilenameFilterIterator.php",
        "$class\\Iterator\\FileTypeFilterIterator" => "$phar/Iterator/FileTypeFilterIterator.php",
        "$class\\Iterator\\RecursiveDirectoryIterator" => "$phar/Iterator/RecursiveDirectoryIterator.php",
        "$class\\Iterator\\FilterIterator" => "$phar/Iterator/FilterIterator.php",
        "$class\\Iterator\\MultiplePcreFilterIterator" => "$phar/Iterator/MultiplePcreFilterIterator.php",
        "$class\\Iterator\\RecursiveDirectoryIterator" => "$phar/Iterator/RecursiveDirectoryIterator.php",
        "$class\\Iterator\\SizeRangeFilterIterator" => "$phar/Iterator/SizeRangeFilterIterator.php",
        "$class\\Iterator\\SortableIterator" => "$phar/Iterator/SortableIterator.php",
        "$class\\Finder" => "$phar/Finder.php",
        "$class\\Glob" => "$phar/Glob.php",
        "$class\\SplFileInfo" => "$phar/SplFileInfo.php"
    );

    foreach ($load as $class => $path) {
        if (false === class_exists($class)) {
            include $path;
        }
    }
}

