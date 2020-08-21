<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/24/18
 * Time: 12:29 PM
 */

namespace App\Modules\ProductRegistration\Entities;


use Illuminate\Database\Eloquent\Model;

class MeetingMember extends Model
{
    protected $fillable = [
        "product_application_meeting_id",
        "member_name"
    ];

    public $table = "tra_product_application_meeting_members";

    public function applicationMeeting() {
        return $this->belongsTo("ApplicationMeeting");
    }
}