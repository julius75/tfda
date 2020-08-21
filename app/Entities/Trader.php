<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 11:40 AM
 */

namespace Modules\ProductRegistration\Entities;


use Illuminate\Database\Eloquent\Model;

class Trader extends Model
{
    protected $fillable = [
      "name",
      "contact_person",
      "tin_no",
      "country_id",
      "region_id",
      "district_id",
      "physical_address",
      "postal_address",
      "telephone_no",
      "code_no",
      "mobile_no",
      "email_address",
      "status_id",
      "identification_no",
      "trader_category_id",
      "created_by",
      "created_on",
      "altered_by",
      "dola",
    ];

    protected $table = "tra_traders";

    public function productApplications() {
        return $this->hasMany("ProductApplication");
    }
}