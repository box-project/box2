<?php

    call_user_func(function()
    {
        $loader = require __DIR__ . '/../vendors/autoload.php';

        $loader->add(null, __DIR__);
    });

    function build_paths(array $paths)
    {
        foreach ($paths as $a => $b)
        {
            if (is_string($a))
            {
                if (false === file_exists($a))
                {
                    mkdir($a);
                }

                $e = array();

                foreach ($b as $c => $d)
                {
                    if (is_string($c))
                    {
                        $e["$a/$c"] = $d;
                    }

                    else
                    {
                        $e[] = "$a/$d";
                    }
                }

                build_paths($e);
            }

            else
            {
                touch($b);
            }
        }
    }

    function rmdir_r($dir)
    {
        foreach(scandir($dir) as $i)
        {
            if (in_array($i, array('.', '..')))
            {
                continue;
            }

            $p = "$dir/$i";

            if (is_dir($p) && ! is_link($p))
            {
                rmdir_r($p);
            }

            else
            {
                unlink($p);
            }
        }

        rmdir($dir);
    }