<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewInventorySourceNotification extends Mailable
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

        $inventory = $this->shipment->inventory_source;

        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($inventory->contact_email, $inventory->name)
            ->subject(trans('admin::app.mail.shipment.subject', ['order_id' => $order->increment_id]))
            ->view('admin::emails.sales.new-inventorysource-shipment');
    }
}
