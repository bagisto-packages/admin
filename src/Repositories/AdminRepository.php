<?php

namespace BagistoPackages\Admin\Repositories;

use BagistoPackages\Shop\Eloquent\Repository;

class AdminRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'BagistoPackages\Admin\Contracts\Admin';
    }
}
