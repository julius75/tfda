<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/26/2019
 * Time: 11:13 AM
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericAttachmentMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $notification_subject = '';
    protected $notification_message = '';
    protected $report = '';
    protected $attachment_name = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($notification_subject, $notification_message, $report, $attachment_name)
    {
        $this->notification_subject = $notification_subject;
        $this->notification_message = $notification_message;
        $this->report = $report;
        $this->attachment_name = $attachment_name;
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
            ->with($data)
            ->attachData($this->report, $this->attachment_name . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
