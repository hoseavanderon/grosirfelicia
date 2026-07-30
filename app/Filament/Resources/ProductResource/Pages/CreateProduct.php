<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductCreationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = Auth::id();
        $service = app(ProductCreationService::class);

        $data['user_id'] = $userId;
        $data['stock_check_status'] = Product::STOCK_CHECK_REQUIRED;
        $data['sort_order'] = $service->nextSortOrder(
            $userId,
            (int) $data['brand_id'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ProductCreationService::class)->finalizeNewProduct($this->getRecord());
    }
}
