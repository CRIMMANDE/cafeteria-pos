<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosInicialesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run()
    {
        DB::table('mesas')->insert([
            ['numero'=>1],
            ['numero'=>2],
            ['numero'=>3],
            ['numero'=>4],
            ['numero'=>5],
            ['numero'=>6],
            ['numero'=>7],
            ['numero'=>8],
            ['numero'=>9],
            ['numero'=>10],
        ]);

        DB::table('categorias')->insert([
            ['nombre'=>'Bebidas','tipo'=>'barra'],
            ['nombre'=>'Comida','tipo'=>'cocina'],
        ]);

        DB::table('productos')->insert([
            ['nombre'=>'Cafe Americano','precio'=>35,'categoria_id'=>1],
            ['nombre'=>'Capuccino','precio'=>45,'categoria_id'=>1],
            ['nombre'=>'Sandwich','precio'=>60,'categoria_id'=>2],
        ]);

        DB::table('extras')->insert([
            ['nombre'=>'Huevo extra','precio'=>12],
            ['nombre'=>'Queso extra','precio'=>10],
            ['nombre'=>'Leche de almendra','precio'=>8],
            ['nombre'=>'Shot extra','precio'=>15],
        ]);
    }
}
