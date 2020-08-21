<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 1/25/2019
 * Time: 10:25 AM
 */

namespace App\Modules\ClinicalTrial\Entities;


use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    protected $table='par_meeting_participants';
    protected $guarded=[];
    const UPDATED_AT = 'dola';
    const CREATED_AT = 'created_on';
}