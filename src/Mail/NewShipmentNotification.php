<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewShipmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The shipment instance.
     *
     * @var \BagistoPackages\Shop\Contracts\Shipment
     */
    public $shipment;

    /**
     * Create a new message instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Shipment $shipment
     * @return void
     */
    public function __construct($shipment)
    {
        $this->shipment = $shipment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = $this->shipment->order;

        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($order->customer_email, $order->customer_full_name)
            ->subject(trans('shop::app.mail.shipment.subject', ['order_id' => $order->increment_id]))
            ->view('shop::emails.sales.new-shipment');
    }
}
