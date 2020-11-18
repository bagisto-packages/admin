<?php

use \BagistoPackages\Admin\Bouncer;

if (!function_exists('bouncer')) {
    function bouncer()
    {
        return app()->make(Bouncer::class);
    }
}
