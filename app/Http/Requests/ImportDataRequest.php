<?php

namespace App\Http\Requests;

use App\Actions\ImportData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class ImportDataRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Only the shape is checked here: each collection is a list of rows. The
     * fields of every row are checked by ImportData with the rules of its PUT.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect(ImportData::COLLECTIONS)
            ->flatMap(fn (string $collection): array => [
                $collection => ['sometimes', 'array', 'list'],
                "{$collection}.*" => ['array'],
            ])
            ->all();
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rows = collect(ImportData::COLLECTIONS)
                    ->sum(fn (string $collection): int => is_array($this->input($collection)) ? count($this->input($collection)) : 0);

                if ($rows > ImportData::MAX_ROWS) {
                    $validator->errors()->add('rows', __('api.import.too_many_rows', ['max' => number_format(ImportData::MAX_ROWS, 0, ',', '.')]));
                }
            },
        ];
    }
}
