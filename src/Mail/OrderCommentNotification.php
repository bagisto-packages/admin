<?php

namespace BagistoPackages\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCommentNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order comment instance.
     *
     * @var  \BagistoPackages\Shop\Contracts\OrderComment $comment
     */
    public $comment;

    /**
     * Create a new message instance.
     *
     * @param \BagistoPackages\Shop\Contracts\OrderComment $comment
     * @return void
     */
    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($this->comment->order->customer_email, $this->comment->order->customer_full_name)
            ->subject(trans('admin::app.mail.order.comment.subject', ['order_id' => $this->comment->order->increment_id]))
            ->view('admin::emails.sales.new-order-comment');
    }
}
