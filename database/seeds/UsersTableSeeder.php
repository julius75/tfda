<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
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
                'first_name'=>'hPE4NbTPeo2R4sHryXY+9w==',
                'last_name'=>'hPE4NbTPeo2R4sHryXY+9w==',
                'title_id'=>1,
                'email'=>'7rRz721uLV1i8obKzSiqEw==',
                'phone'=>'qImgx8IkBSUsutvQO7Wosg==',
                'mobile'=>'qImgx8IkBSUsutvQO7Wosg==',
                'gender_id'=>1,
                'password'=>'$6$536ad3eae4f968b5$XarYMdgpKqqn5mIJTFU2kqHiHrpbKdGjDGve7qUnWXmUPX5L0DuF4I0fj0d3Nw97i7Cqh2RuJ6dpN9Fsq/09z1',
                'uuid'=>'947b876a30ea1cb1056'
            )
        );
        DB::table('users')->insert($seeder);
    }
}
