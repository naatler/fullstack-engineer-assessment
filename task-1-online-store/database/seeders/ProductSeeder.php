<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Flash Sale Product',
                'price' => 10000,
                'stock' => 10,
            ]
        );
    }
}