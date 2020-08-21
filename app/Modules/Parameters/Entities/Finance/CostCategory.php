<?php
/**
 * Created by Softclans.
 * User: robinson
 * Date: 9/12/18
 * Time: 12:29 PM
 */

namespace App\Modules\Parameters\Entities\Finance;


use App\Modules\Parameters\Entities\AbstractParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Parameters\Entities\GetDataTrait;

class CostCategory extends AbstractParameter {
    protected $fillable = [
        "name",
        "code",
        "description",
        "created_by",
        "created_at",
        "is_enabled",
        "dola",
        "altered_by"
    ];

    protected $table = 'par_cost_categories';

    use GetDataTrait;
}