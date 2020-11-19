<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewRefundNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The refund instance.
     *
     * @var \BagistoPackages\Shop\Contracts\Refund
     */
    public $refund;

    /**
     * Create a new message instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Refund $refund
     * @return void
     */
    public function __construct($refund)
    {
        $this->refund = $refund;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = $this->refund->order;

        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($order->customer_email, $order->customer_full_name)
            ->subject(trans('admin::app.mail.refund.subject', ['order_id' => $order->increment_id]))
            ->view('admin::emails.sales.new-refund');
    }
}
