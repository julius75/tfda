<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessLevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seeder=array(
            array(
                'name'=>'None',
                'description'=>'No Access'
            ),
            array(
                'name'=>'Read Only',
                'description'=>'Can read only'
            ),
            array(
                'name'=>'Write & Update',
                'description'=>'Can write and update'
            ),
            array(
                'name'=>'Full Access',
                'description'=>'System Full Access'
            )
        );
        DB::table('par_accesslevels')->insert($seeder);
    }
}
