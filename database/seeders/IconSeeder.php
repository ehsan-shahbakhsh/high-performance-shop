<?php

namespace Database\Seeders;

use BladeUI\Icons\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('icons')->truncate();

        $factory = app(Factory::class);

        $iconsToInsert = [];
        $chunkSize = 1000;

        foreach ($factory->all() as $set) {
            $prefix = $set['prefix'];

            $this->command->info("Processing set: {$prefix}...");

            foreach ($set['paths'] as $path) {
                $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($directory);

                foreach ($iterator as $file) {
                    if ($file->getExtension() !== 'svg') continue;

                    $filename = $file->getBasename('.svg');

                    $fullPath = $file->getPath();
                    $relativePath = ltrim(str_replace($path, '', $fullPath), DIRECTORY_SEPARATOR);

                    $iconName = $relativePath
                        ? str_replace(DIRECTORY_SEPARATOR, '-', $relativePath) . '-' . $filename
                        : $filename;

                    $fullName = "$prefix-$iconName";

                    $iconsToInsert[] = [
                        'set' => $prefix,
                        'name' => $iconName,
                        'full_name' => $fullName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($iconsToInsert) >= $chunkSize) {
                        DB::table('icons')->insert($iconsToInsert);
                        $iconsToInsert = [];
                        $this->command->info("Indexed 1000 icons...");
                    }
                }
            }
        }

        if (!empty($iconsToInsert)) {
            DB::table('icons')->insert($iconsToInsert);
        }

        $this->command->info("All icons indexed successfully.");
    }
}
