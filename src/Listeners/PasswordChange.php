<?php

namespace BagistoPackages\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use BagistoPackages\Shop\Notifications\AdminUpdatePassword;
use BagistoPackages\Shop\Notifications\CustomerUpdatePassword;

class PasswordChange
{
    /**
     * Send mail on updating password.
     *
     * @param \BagistoPackages\Shop\Models\Customer|\BagistoPackages\Shop\Models\Admin $adminOrCustomer
     * @return void
     */
    public function sendUpdatePasswordMail($adminOrCustomer)
    {
        try {
            if ($adminOrCustomer instanceof \BagistoPackages\Customer\Models\Customer) {
                Mail::queue(new CustomerUpdatePassword($adminOrCustomer));
            }

            if ($adminOrCustomer instanceof \BagistoPackages\User\Models\Admin) {
                Mail::queue(new AdminUpdatePassword($adminOrCustomer));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
