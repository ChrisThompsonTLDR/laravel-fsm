<?php

declare(strict_types=1);

namespace Fsm\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Request DTO for getting FSM transition history.
 *
 * Validates and structures the input parameters required to retrieve
 * the complete transition history for a specific FSM instance.
 */
class ReplayHistoryRequest extends Dto
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

            // Validate array-based instantiation has required fields (using camelCase keys)
            if (! array_key_exists('modelClass', $prepared) || ! is_string($prepared['modelClass']) || trim($prepared['modelClass']) === '') {
                throw new \InvalidArgumentException(
                    'The modelClass is required and cannot be an empty string.'
                );
            }

            if (! array_key_exists('modelId', $prepared) || ! is_string($prepared['modelId']) || trim($prepared['modelId']) === '') {
                throw new \InvalidArgumentException(
                    'The modelId is required and cannot be an empty string.'
                );
            }

            if (! array_key_exists('columnName', $prepared) || ! is_string($prepared['columnName']) || trim($prepared['columnName']) === '') {
                throw new \InvalidArgumentException(
                    'The columnName is required and cannot be an empty string.'
                );
            }

            parent::__construct($prepared);

            return;
        }

        // Validate positional parameters
        if (! is_string($modelClass) || trim($modelClass) === '') {
            throw new \InvalidArgumentException(
                'The modelClass is required and cannot be an empty string.'
            );
        }

        if (trim($modelId) === '') {
            throw new \InvalidArgumentException(
                'The modelId is required and cannot be an empty string.'
            );
        }

        if (trim($columnName) === '') {
            throw new \InvalidArgumentException(
                'The columnName is required and cannot be an empty string.'
            );
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
