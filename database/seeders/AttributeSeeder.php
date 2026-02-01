<?php

namespace Database\Seeders;

use App\Enums\AttributeType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws \Throwable
     */
    public function run(): void
    {
        DB::transaction(function () {
            $elecSetId = DB::table('attribute_sets')->insertGetId([
                'name' => 'کالای دیجیتال',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $techGroupId = DB::table('attribute_groups')->insertGetId([
                'attribute_set_id' => $elecSetId,
                'name' => 'مشخصات فنی',
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ramAttrId = $this->createAttribute('ram', 'حافظه RAM', AttributeType::Select, true, true);
            $this->attachToGroup($techGroupId, $ramAttrId, 1);
            $this->createOptions($ramAttrId, [
                ['label' => '8 GB', 'value' => '8gb'],
                ['label' => '16 GB', 'value' => '16gb'],
                ['label' => '32 GB', 'value' => '32gb'],
            ]);

            $storageAttrId = $this->createAttribute('storage', 'حافظه داخلی', AttributeType::Select, true, true);
            $this->attachToGroup($techGroupId, $storageAttrId, 2);
            $this->createOptions($storageAttrId, [
                ['label' => '256 GB SSD', 'value' => '256gb'],
                ['label' => '512 GB SSD', 'value' => '512gb'],
                ['label' => '1 TB SSD', 'value' => '1tb'],
            ]);

            $cpuAttrId = $this->createAttribute('cpu', 'پردازنده', AttributeType::Text, true, true);
            $this->attachToGroup($techGroupId, $cpuAttrId, 3);

            $visualGroupId = DB::table('attribute_groups')->insertGetId([
                'attribute_set_id' => $elecSetId,
                'name' => 'مشخصات ظاهری',
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $colorAttrId = $this->createAttribute('color', 'رنگ بدنه', AttributeType::Color, true, true);
            $this->attachToGroup($visualGroupId, $colorAttrId, 1);
            $this->createOptions($colorAttrId, [
                ['label' => 'مشکی فضایی', 'value' => '#333333'],
                ['label' => 'نقره‌ای', 'value' => '#C0C0C0'],
                ['label' => 'آبی تیره', 'value' => '#000080'],
            ]);


            $clothSetId = DB::table('attribute_sets')->insertGetId([
                'name' => 'پوشاک',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $generalGroupId = DB::table('attribute_groups')->insertGetId([
                'attribute_set_id' => $clothSetId,
                'name' => 'اطلاعات عمومی',
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $materialAttrId = $this->createAttribute('material', 'جنس پارچه', AttributeType::Select, true, false);
            $this->attachToGroup($generalGroupId, $materialAttrId, 1);
            $this->createOptions($materialAttrId, [
                ['label' => 'نخ پنبه', 'value' => 'cotton'],
                ['label' => 'پلی‌استر', 'value' => 'polyester'],
                ['label' => 'کتان', 'value' => 'linen'],
            ]);

            $sizeAttrId = $this->createAttribute('size', 'سایز لباس', AttributeType::Select, true, true);
            $this->attachToGroup($generalGroupId, $sizeAttrId, 2);
            $this->createOptions($sizeAttrId, [
                ['label' => 'S (کوچک)', 'value' => 's'],
                ['label' => 'M (متوسط)', 'value' => 'm'],
                ['label' => 'L (بزرگ)', 'value' => 'l'],
                ['label' => 'XL (خیلی بزرگ)', 'value' => 'xl'],
            ]);

            $this->attachToGroup($generalGroupId, $colorAttrId, 3);
        });
    }

    private function createAttribute(string $code, string $name, AttributeType $type, bool $isFilterable, bool $isRequired): int
    {
        $exists = DB::table('attributes')->where('code', $code)->first();
        if ($exists) return $exists->id;

        return DB::table('attributes')->insertGetId([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_filterable' => $isFilterable,
            'is_required' => $isRequired,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachToGroup($groupId, $attrId, $position): void
    {
        DB::table('attribute_attribute_group')->insert([
            'attribute_group_id' => $groupId,
            'attribute_id' => $attrId,
            'position' => $position,
        ]);
    }

    private function createOptions($attrId, array $options): void
    {
        foreach ($options as $index => $opt) {
            DB::table('attribute_options')->insert([
                'attribute_id' => $attrId,
                'label' => $opt['label'],
                'value' => $opt['value'],
                'position' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
