<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancelOrderAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var \BagistoPackages\Shop\Contracts\Order
     */
    public $order;

    /**
     * @param \BagistoPackages\Shop\Contracts\Order $order
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to(core()->getAdminEmailDetails()['email'])
            ->subject(trans('shop::app.mail.order.cancel.subject'))
            ->view('shop::emails.sales.order-cancel-admin');
    }
}
