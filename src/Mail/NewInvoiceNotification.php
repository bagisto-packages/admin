<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewInvoiceNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The invoice instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Invoice $invoice
     */
    public $invoice;

    /**
     * Create a new message instance.
     *
     * @param \BagistoPackages\Shop\Contracts\Invoice $invoice
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = $this->invoice->order;

        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($order->customer_email, $order->customer_full_name)
            ->subject(trans('admin::app.mail.invoice.subject', ['order_id' => $order->increment_id]))
            ->view('admin::emails.sales.new-invoice');
    }
}
