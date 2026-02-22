<?php

if (!function_exists('tenant')) {
    function tenant()
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}