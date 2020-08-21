<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 11:33 AM
 */

namespace App\Modules\ProductRegistration\Entities;


use Illuminate\Database\Eloquent\Model;

class ProductApplication extends Model
{
    protected $fillable = [
      "application_code",
      "reference_no",
      "trader_id",
      "sub_module_id",
      "section_id",
      "product_id",
      "zone_id",
      "local_agent_id",
      "assessment_procedure_id",
      "date_added",
      "submission_date",
      "status_id",
      "created_by",
      "created_on",
      "altered_by",
      "dola",
    ];

    protected $table = "tra_product_applications";

    public function product() {
        return $this->belongsTo("ProductInformation");
    }

    public function trader() {
        return $this->belongsTo("Trader");
    }

    public function zone() {
        return $this->belongsTo("Zone");
    }

    public function section() {
        return $this->belongsTo("Section");
    }

    public function LocalAgent() {
        return $this->belongsTo("Trader");
    }

    public function AssessmentProcedure() {
        return $this->belongsTo("AssessmentProcedure");
    }
}