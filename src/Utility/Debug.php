<?php

namespace TypeRocket\Utility;

class Debug
{

    /**
     * Die and Dump Vars
     *
     * @param $param
     */
    public static function dd($param)
    {
        call_user_func_array('var_dump', func_get_args());
        exit();
    }
}