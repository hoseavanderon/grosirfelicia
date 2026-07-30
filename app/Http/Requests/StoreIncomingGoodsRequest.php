<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreIncomingGoodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.expired' => ['required', 'date'],
            'rows.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => 'Tambahkan minimal satu baris barang masuk.',
            'rows.*.product_id.required' => 'Produk wajib dipilih.',
            'rows.*.expired.required' => 'Tanggal expired wajib diisi.',
            'rows.*.quantity.required' => 'Jumlah wajib diisi.',
            'rows.*.quantity.min' => 'Jumlah harus lebih dari 0.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $userId = Auth::id();
            $rows = $this->input('rows', []);

            foreach ($rows as $index => $row) {
                if (empty($row['product_id'])) {
                    continue;
                }

                $ownsProduct = \App\Models\Product::query()
                    ->where('user_id', $userId)
                    ->where('id', $row['product_id'])
                    ->exists();

                if (! $ownsProduct) {
                    $validator->errors()->add(
                        "rows.{$index}.product_id",
                        'Produk tidak valid.',
                    );
                }
            }
        });
    }
}
