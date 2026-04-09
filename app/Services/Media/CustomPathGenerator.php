<?php

namespace App\Services\Media;

use App\Models\Product;
use App\Models\ProductVariant;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class CustomPathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        if ($media->model_type === Product::class) {
            return "products/{$media->model_id}/{$media->collection_name}";
        }

        if ($media->model_type === ProductVariant::class) {
            $media->loadMissing('model');
            $model = $media->model;

            return "products/{$model->product_id}/variants/{$model->id}/{$media->collection_name}";
        }

        return parent::getBasePath($media);
    }
}
