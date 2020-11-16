<?php

namespace BagistoPackages\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use BagistoPackages\Admin\Mail\NewAdminNotification;
use BagistoPackages\Admin\Mail\NewOrderNotification;
use BagistoPackages\Admin\Mail\NewRefundNotification;
use BagistoPackages\Admin\Mail\NewInvoiceNotification;
use BagistoPackages\Admin\Mail\CancelOrderNotification;
use BagistoPackages\Admin\Mail\NewShipmentNotification;
use BagistoPackages\Admin\Mail\OrderCommentNotification;
use BagistoPackages\Admin\Mail\CancelOrderAdminNotification;
use BagistoPackages\Admin\Mail\NewInventorySourceNotification;

class Order
{
    /**
     * Send new order Mail to the customer and admin
     *
     * @param \BagistoPackages\Shop\Contracts\Order $order
     * @return void
     */
    public function sendNewOrderMail($order)
    {
        $customerLocale = $this->getLocale($order);

        try {
            /* email to customer */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-order';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail($customerLocale, new NewOrderNotification($order));
            }

            /* email to admin */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-admin';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail(env('APP_LOCALE'), new NewAdminNotification($order));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send new invoice mail to the customer
     *
     * @param \BagistoPackages\Shop\Contracts\Invoice $invoice
     * @return void
     */
    public function sendNewInvoiceMail($invoice)
    {
        $customerLocale = $this->getLocale($invoice);

        try {
            if ($invoice->email_sent) {
                return;
            }

            /* email to customer */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-invoice';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail($customerLocale, new NewInvoiceNotification($invoice));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send new refund mail to the customer
     *
     * @param \BagistoPackages\Shop\Contracts\Refund $refund
     * @return void
     */
    public function sendNewRefundMail($refund)
    {
        $customerLocale = $this->getLocale($refund);

        try {
            /* email to customer */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-refund';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail($customerLocale, new NewRefundNotification($refund));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send new shipment mail to the customer
     *
     * @param \BagistoPackages\Shop\Contracts\Shipment $shipment
     * @return void
     */
    public function sendNewShipmentMail($shipment)
    {
        $customerLocale = $this->getLocale($shipment);

        try {
            if ($shipment->email_sent) {
                return;
            }

            /* email to customer */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-shipment';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail($customerLocale, new NewShipmentNotification($shipment));
            }

            /* email to admin */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-inventory-source';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail(env('APP_LOCALE'), new NewInventorySourceNotification($shipment));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * @param \BagistoPackages\Shop\Contracts\Order $order
     * @return void
     */
    public function sendCancelOrderMail($order)
    {
        $customerLocale = $this->getLocale($order);

        try {
            /* email to customer */
            $configKey = 'emails.general.notifications.emails.general.notifications.cancel-order';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail($customerLocale, new CancelOrderNotification($order));
            }

            /* email to admin */
            $configKey = 'emails.general.notifications.emails.general.notifications.new-admin';
            if (core()->getConfigData($configKey)) {
                $this->prepareMail(env('APP_LOCALE'), new CancelOrderAdminNotification($order));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * @param \BagistoPackages\Shop\Contracts\OrderComment $comment
     * @return void
     */
    public function sendOrderCommentMail($comment)
    {
        $customerLocale = $this->getLocale($comment);

        if (!$comment->customer_notified) {
            return;
        }

        try {
            /* email to customer */
            $this->prepareMail($customerLocale, new OrderCommentNotification($comment));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Get the locale of the customer if somehow item name changes then the english locale will pe provided.
     *
     * @param object \BagistoPackages\Sales\Contracts\Order|\BagistoPackages\Sales\Contracts\Invoice|\BagistoPackages\Sales\Contracts\Refund|\BagistoPackages\Sales\Contracts\Shipment|\BagistoPackages\Sales\Contracts\OrderComment
     * @return string
     */
    private function getLocale($object)
    {
        if ($object instanceof \BagistoPackages\Sales\Contracts\OrderComment) {
            $object = $object->order;
        }

        $objectFirstItem = $object->items->first();
        return isset($objectFirstItem->additional['locale']) ? $objectFirstItem->additional['locale'] : 'en';
    }

    /**
     * Prepare Mail.
     * @return void
     */
    private function prepareMail($locale, $notification)
    {
        app()->setLocale($locale);
        Mail::queue($notification);
    }
}
