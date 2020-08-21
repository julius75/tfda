<?php

use Illuminate\Database\Seeder;

class ConfirmationsTableSeeder extends Seeder
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
                'name'=>'Yes',
                'flag'=>1,
                'description'=>'Accepted'
            ),
            array(
                'name'=>'No',
                'flag'=>0,
                'description'=>'Declined'
            )
        );
        DB::table('par_confirmations')->insert($seeder);
    }
}
