<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 2/19/2019
 * Time: 2:43 PM
 */

namespace App\Helpers;

use App\Mail\GenericPlainMail;
use App\Mail\GenericAttachmentMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPassword;

class EmailHelper
{

    static function getEmailTemplateInfo($template_id, $vars)
    {
        $template_info = DB::table('email_messages_templates')
            ->where('id', $template_id)
            ->first();
        if (is_null($template_info)) {
            $template_info = (object)array(
                'subject' => 'Error',
                'body' => 'Sorry this email was delivered wrongly, kindly ignore.'
            );
        }
        $template_info->subject = strtr($template_info->subject, $vars);
        $template_info->body = strtr($template_info->body, $vars);
        return $template_info;
    }

    static function onlineApplicationNotificationMail($template_id, $email, $vars)
    {
        $template_info = self::getEmailTemplateInfo($template_id, $vars);
        $subject = $template_info->subject;
        $message = $template_info->body;
        Mail::to($email)->send(new GenericPlainMail($subject, $message));
    }

    static function forgotPasswordEmail($template_id, $email, $link, $vars)
    {
        $template_info = self::getEmailTemplateInfo($template_id, $vars);
        $subject = $template_info->subject;
        $message = $template_info->body;
        Mail::to($email)->send(new ForgotPassword($subject, $message, $link));
        if (count(Mail::failures()) > 0) {
            $res = array(
                'success' => false,
                'message' => 'Problem was encountered while sending email. Please try again later!!'
            );
        } else {
            $res = array(
                'success' => true,
                'message' => 'Password reset instructions sent to your email address!!'
            );
        }
        return $res;
    }

    static function applicationInvoiceEmail($template_id, $email, $vars, $report, $attachment_name)
    {
        $template_info = self::getEmailTemplateInfo($template_id, $vars);
        $subject = $template_info->subject;
        $message = $template_info->body;
        Mail::to($email)->send(new GenericAttachmentMail($subject, $message, $report, $attachment_name));
    }

}