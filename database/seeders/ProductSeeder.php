<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Fictional starter catalog for a delicatessen/grocery business —
     * realistic enough to build and search order forms against, not meant
     * to reflect a real product range or real prices.
     * Run with: php artisan db:seed --class=ProductSeeder
     */
    protected array $products = [
        ['article_number' => 'ART-1001', 'name' => 'Salami', 'price' => 8.50],
        ['article_number' => 'ART-1002', 'name' => 'Groene olijven', 'price' => 4.20],
        ['article_number' => 'ART-1003', 'name' => 'Zwarte olijven', 'price' => 4.20],
        ['article_number' => 'ART-1004', 'name' => 'Vleeswaren tranches (gemengd)', 'price' => 6.75],
        ['article_number' => 'ART-1005', 'name' => 'Rundvlees (boeuf)', 'price' => 14.90],
        ['article_number' => 'ART-1006', 'name' => 'Geitenkaas (chèvre)', 'price' => 7.30],
        ['article_number' => 'ART-1007', 'name' => 'Coca-Cola 1,5L', 'price' => 2.10],
        ['article_number' => 'ART-1008', 'name' => 'Biscuits (koekjes)', 'price' => 3.00],
        ['article_number' => 'ART-1009', 'name' => 'Melk (lait) 1L', 'price' => 1.35],
        ['article_number' => 'ART-1010', 'name' => 'Brood (stokbrood)', 'price' => 2.50],
        ['article_number' => 'ART-1011', 'name' => 'Kaas belegen', 'price' => 9.40],
        ['article_number' => 'ART-1012', 'name' => 'Gerookte ham', 'price' => 11.20],
        ['article_number' => 'ART-1013', 'name' => 'Hummus', 'price' => 3.80],
        ['article_number' => 'ART-1014', 'name' => 'Taboulé', 'price' => 4.50],
        ['article_number' => 'ART-1015', 'name' => 'Sinaasappelsap 1L', 'price' => 2.75],
    ];

    /**
     * Create the fictional catalog (idempotent — safe to run multiple times).
     */
    public function run(): void
    {
        foreach ($this->products as $product) {
            Product::updateOrCreate(
                ['article_number' => $product['article_number']],
                $product
            );
        }

        $this->command->info('Products created: '.count($this->products));
    }
}
