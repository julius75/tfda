<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;
    protected $link = '';
    protected $notification_subject = '';
    protected $notification_message = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($notification_subject, $notification_message, $link)
    {
        $this->link = $link;
        $this->notification_subject = $notification_subject;
        $this->notification_message = $notification_message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data['resetLink'] = $this->link;
        $data['notification_subject'] = $this->notification_subject;
        $data['notification_message'] = $this->notification_message;
        return $this->view('mail.forgotPassword')
            ->subject($this->notification_subject)
            ->with($data);
    }
}
