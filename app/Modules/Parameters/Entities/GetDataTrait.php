<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 10/4/18
 * Time: 5:01 PM
 */

namespace App\Modules\Parameters\Entities;

trait GetDataTrait {
    public static function getData($start, $limit, $doRetrieveAll, $filter) {
        return parent::get($start, $limit, $doRetrieveAll,  __CLASS__, $filter);
    }
}