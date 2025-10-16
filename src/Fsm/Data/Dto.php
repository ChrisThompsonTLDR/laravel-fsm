<?php

declare(strict_types=1);

namespace Fsm\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;

abstract class Dto extends ArgonautDTO
{
    /**
     * @param  array<mixed>  $value
     */
    protected static function isAssociative(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * Check if array has only string keys (no numeric keys).
     *
     * @param  array<mixed>  $value
     */
    protected static function hasOnlyStringKeys(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (! is_string($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if array has at least one string key (allows mixed keys arrays).
     *
     * @param  array<mixed>  $value
     */
    protected static function hasStringKeys(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if array is a callable array (e.g., ['ClassName', 'method'] or [object, 'method']).
     *
     * @param  array<mixed>  $value
     */
    public static function isCallableArray(array $value): bool
    {
        // Must have exactly 2 elements
        if (count($value) !== 2) {
            return false;
        }

        $keys = array_keys($value);

        // Must have numeric keys 0 and 1
        if ($keys !== [0, 1]) {
            return false;
        }

        // Second element must be a non-empty string (method name)
        if (! is_string($value[1]) || empty($value[1])) {
            return false;
        }

        // First element must be either:
        // 1. An object instance, OR
        // 2. A non-empty string (class name)
        // We don't try to validate if the string "looks like" a class name
        // because that's unreliable and leads to false positives/negatives.
        // The actual callable validation happens at invocation time.
        if (is_object($value[0])) {
            return true;
        }

        if (is_string($value[0]) && ! empty($value[0])) {
            return true;
        }

        return false;
    }

    /**
     * Check if array is meant for DTO construction (has DTO property keys).
     * This is more sophisticated than just checking if it's associative.
     *
     * @param  array<mixed>  $value
     * @param  array<string>  $expectedKeys
     */
    public static function isDtoPropertyArray(array $value, array $expectedKeys = []): bool
    {
        // If empty, not a DTO property array
        if (empty($value)) {
            return false;
        }

        // If it's a callable array, it's not for DTO construction
        if (static::isCallableArray($value)) {
            return false;
        }

        // If it's purely numeric indexed, it's not for DTO construction
        if (! static::isAssociative($value)) {
            return false;
        }

        // If we have expected keys, check if any of them exist
        if (! empty($expectedKeys)) {
            foreach ($expectedKeys as $key) {
                if (array_key_exists($key, $value)) {
                    return true;
                }
            }

            return false;
        }

        // If no expected keys provided, check if it has any string keys
        // (this is a fallback for when we don't know the expected structure)
        return static::hasOnlyStringKeys($value);
    }

    /**
     * Validate array structure for DTO construction.
     * Ensures the array is suitable for array-based construction.
     *
     * @param  array<mixed>  $value
     * @param  array<string>  $expectedKeys
     *
     * @throws InvalidArgumentException
     */
    protected static function validateArrayForConstruction(array $value, array $expectedKeys = []): void
    {
        // Must be non-empty
        if (empty($value)) {
            throw new InvalidArgumentException('Array-based construction requires a non-empty array.');
        }

        // Must not be a callable array (check this first)
        if (static::isCallableArray($value)) {
            throw new InvalidArgumentException('Array-based construction cannot use callable arrays.');
        }

        // Must be associative
        if (! static::isAssociative($value)) {
            throw new InvalidArgumentException('Array-based construction requires an associative array.');
        }

        // Must have at least one string key (mixed keys arrays are allowed)
        if (! static::hasStringKeys($value)) {
            throw new InvalidArgumentException('Array-based construction requires an array with at least one string key.');
        }

        // If expected keys are provided, validate at least one exists
        if (! empty($expectedKeys)) {
            $hasExpectedKey = false;
            foreach ($expectedKeys as $key) {
                if (array_key_exists($key, $value)) {
                    $hasExpectedKey = true;
                    break;
                }
            }

            if (! $hasExpectedKey) {
                throw new InvalidArgumentException('Array-based construction requires at least one expected key: '.implode(', ', $expectedKeys));
            }
        }
    }

    /**
     * @param  array<string|int, mixed>  $attributes
     * @return array<string|int, mixed>
     */
    protected static function prepareAttributes(array $attributes, array $defaults = []): array
    {
        $result = $defaults; // Start with defaults

        // First pass: identify which camelCase keys have explicit camelCase input keys
        $explicitCamelCaseKeys = [];
        foreach ($attributes as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $camelKey = Str::camel($key);

            // If the key is already camelCase, mark it as explicitly provided
            if ($key === $camelKey) {
                $explicitCamelCaseKeys[$camelKey] = true;
            }
        }

        // Second pass: set values, but skip snake_case keys if their camelCase equivalent has an explicit camelCase key
        foreach ($attributes as $key => $value) {
            if (! is_string($key)) {
                $result[$key] = $value;
                continue;
            }

            // Convert Collection instances to arrays for proper casting
            if ($value instanceof Collection) {
                $value = $value->all();
            }

            $camelKey = Str::camel($key);

            // Skip snake_case keys if their camelCase equivalent has an explicit camelCase input key
            if ($key !== $camelKey && isset($explicitCamelCaseKeys[$camelKey])) {
                continue;
            }

            $result[$camelKey] = $value;
        }

        return $result;
    }

    /**
     * @return array<string|int, mixed>
     */
    protected static function guardAssociativePayload(mixed $payload): array
    {
        if (! is_array($payload) || ! static::isAssociative($payload)) {
            throw new InvalidArgumentException('Payload must be an associative array of attributes.');
        }

        return static::prepareAttributes($payload);
    }

    public static function from(mixed $payload): static
    {
        if ($payload instanceof static) {
            return $payload;
        }

        if ($payload instanceof Request) {
            $payload = $payload->all();
        }

        if ($payload instanceof Arrayable) {
            $payload = $payload->toArray();
        }

        /** @var class-string<static> $class */
        $class = static::class;

        if (is_array($payload)) {
            // Pass array as single argument - DTOs handle this via their constructor pattern
            // The constructor checks: if (is_array($firstParam) && func_num_args() === 1 && static::isAssociative($firstParam))
            // and then calls prepareAttributes() internally
            $instance = new $class($payload);
        } elseif (is_object($payload)) {
            $instance = new $class(get_object_vars($payload));
        } else {
            throw new InvalidArgumentException('Unable to create DTO from payload of type: '.get_debug_type($payload));
        }

        if (method_exists($instance, 'rules')) {
            $instance->validate();
        }

        return $instance;
    }

    /**
     * @param  iterable<mixed>  $items
     * @return Collection<int, static>
     */
    public static function fromCollection(iterable $items): Collection
    {
        $results = [];

        foreach ($items as $item) {
            $results[] = static::from($item);
        }

        /** @var Collection<int, static> $collection */
        $collection = Collection::make($results);

        return $collection;
    }

    /**
     * @param  iterable<mixed>  $items
     * @return Collection<int, static>
     */
    public static function collect(iterable $items = []): Collection
    {
        return static::fromCollection($items);
    }
}
