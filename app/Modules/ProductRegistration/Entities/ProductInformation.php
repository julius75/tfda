<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/11/18
 * Time: 10:13 AM
 */

namespace App\Modules\ProductRegistration\Entities;


use Illuminate\Database\Eloquent\Model;
use Modules\Parameters\Entities\GetDataTrait;

class ProductInformation extends Model {

    protected $fillable = [
        "atc_code_id",
        "classification_id",
        "brand_name",
        "device_type_id",
        "physical_description",
        "dosage_form_id",
        "product_form_id",
        "product_strength",
        "storage_condition_id",
        "product_type_id",
        "product_category_id",
        "distribution_category_id",
        "special_category_id",
        "product_subcategory_id",
        "intended_end_user_id",
        "intended_use_id",
        "route_of_administration_id",
        "method_of_use_id",
        "section_id",
        "gmdn_code",
        "gmdn_category",
        "gmdn_term",
        "created_by",
        "created_on",
        "altered_by",
        "dola",
    ];

    public $timestamps = false;

    protected $table = "tra_product_information";

    use GetDataTrait;

    public function applications() {
        return $this->hasMany("ProductApplication");
    }

    public function classification() {
        return $this->belongsTo("Classification");
    }

    public function dosageForm() {
        return $this->belongsTo("DosageForm");
    }

    public function productForm() {
        return $this->belongsTo("ProductForm");
    }

    public function atcCode() {
        return $this->belongsTo("AtcCode");
    }

    public function storageCondition() {
        return $this->belongsTo("StorageCondition");
    }

    public function productType() {
        return $this->belongsTo("ProductType");
    }

    public function productCategory() {
        return $this->belongsTo("ProductCategory");
    }

    public function distributionCategory() {
        return $this->belongsTo("DistributionCategory");
    }

    public function specialCategory() {
        return $this->belongsTo("SpecialCategory");
    }

    public function productSubCategory() {
        return $this->belongsTo("productSubCategory");
    }

    public function intendedEndUser() {
        return $this->belongsTo("IntendedEndUser");
    }

    public function intendedUse() {
        return $this->belongsTo("IntendedUse");
    }

    public function RouteOfAdministration() {
        return $this->belongsTo("RouteOfAdministration");
    }

    public function methodOfUse() {
        return $this->belongsTo("MethodOfUse");
    }

    public function section() {
        return $this->belongsTo("Section");
    }
}