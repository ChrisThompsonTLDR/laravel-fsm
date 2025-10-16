<?php

declare(strict_types=1);

namespace Fsm\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Request DTO for getting FSM transition statistics.
 *
 * Validates and structures the input parameters required to retrieve
 * analytics and statistics for FSM usage patterns.
 */
class ReplayStatisticsRequest extends Dto
{
    public string $modelClass;

    public string $modelId;

    public string $columnName;

    /**
     * @param  array<string, string>|string  $modelClass
     */
    public function __construct(
        string|array $modelClass,
        string $modelId = '',
        string $columnName = '',
    ) {
        if (is_array($modelClass) && func_num_args() === 1 && static::isAssociative($modelClass)) {
            // First prepare attributes to convert snake_case to camelCase
            $prepared = static::prepareAttributes($modelClass);

            // Ensure all required keys are present with default values
            $data = array_merge([
                'modelClass' => '',
                'modelId' => '',
                'columnName' => '',
            ], $prepared);

            parent::__construct($data);

            return;
        }

        parent::__construct([
            'modelClass' => $modelClass,
            'modelId' => $modelId,
            'columnName' => $columnName,
        ]);
    }

    /**
     * Validate that the model class is a valid Eloquent model.
     *
     * @return array<string, array<int, string|callable>>
     */
    public static function rules(): array
    {
        return [
            'modelClass' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! class_exists($value)) {
                        $fail("The {$attribute} must be a valid class name.");

                        return;
                    }

                    if (! is_subclass_of($value, Model::class)) {
                        $fail("The {$attribute} must be an Eloquent model class.");
                    }
                },
            ],
            'modelId' => ['required', 'string'],
            'columnName' => ['required', 'string'],
        ];
    }
}
