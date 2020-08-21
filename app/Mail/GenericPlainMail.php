<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/26/2019
 * Time: 10:24 AM
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericPlainMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $notification_subject = '';
    protected $notification_message = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($notification_subject, $notification_message)
    {
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
        $data['notification_subject'] = $this->notification_subject;
        $data['notification_message'] = $this->notification_message;
        return $this->view('mail.genericEmail')
            ->subject($this->notification_subject)
            ->with($data);
    }
}
