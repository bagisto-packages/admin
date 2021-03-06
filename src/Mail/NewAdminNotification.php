<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \BagistoPackages\Shop\Contracts\Order
     */
    public $order;

    /**
     * Create a new message instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Order $order
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to(core()->getAdminEmailDetails()['email'])
            ->subject(trans('admin::app.mail.order.subject'))
            ->view('admin::emails.sales.new-admin-order');
    }
}
