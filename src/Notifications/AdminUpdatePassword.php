<?php

namespace BagistoPackages\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminUpdatePassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The admin instance.
     *
     * @var  \BagistoPackages\Shop\Contracts\Admin $admin
     */
    public $admin;

    /**
     * Create a new admin instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Admin $admin
     * @return void
     */
    public function __construct($admin)
    {
        $this->admin = $admin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($this->admin->email, $this->admin->name)
            ->subject(trans('admin::app.mail.update-password.subject'))
            ->view('admin::emails.notifications.update-password', ['user' => $this->admin]);
    }
}
