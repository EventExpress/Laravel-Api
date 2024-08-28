<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    /**
    php artisan db:seed --class=TypeUserSeeder
     **/
    public function run(): void
    {
        $type = [
            [
                'tipousu' => 'Locatario',
                'created_at' => Carbon::now(),
            ],
            [
                'tipousu' => 'Locador',
                'created_at' => Carbon::now(),
            ],
            [
                'tipousu' => 'Prestador',
                'created_at' => Carbon::now(),
            ],
        ];

        DB::table('type_users')->insert($type);
    }
}
