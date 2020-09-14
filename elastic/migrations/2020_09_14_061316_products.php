<?php   declare(strict_types=1);

use ElasticAdapter\Indices\Mapping;
use ElasticAdapter\Indices\Settings;
use ElasticMigrations\Facades\Index;
use ElasticMigrations\MigrationInterface;

final class Products implements MigrationInterface
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Index::createIfNotExists('products', function (Mapping $mapping, Settings $settings) {
            // you can change the index settings
            $settings->index([
                'number_of_replicas' => 1,
                'number_of_shards' => 1,
            ]);

            $mapping->keyword('title');
            $mapping->scaledFloat('price',['scaling_factor' => 100]);
            $mapping->keyword('designer');
            $mapping->keyword('size');
            $mapping->keyword('tags');
            $mapping->text('summary');
            $mapping->object('product_id',['enabled'=>false]);
            $mapping->object('thumbnail',['enabled'=>false]);


        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::dropIfExists('products');
    }
}
