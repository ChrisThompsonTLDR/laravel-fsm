<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Fsm\Enums\TestFeatureState;

/**
 * Edge case tests for TransitionDefinition validation consistency.
 *
 * This test focuses on edge cases and ensures that the validation logic
 * is consistent between different construction methods and handles
 * various edge cases correctly.
 */
class TransitionDefinitionValidationEdgeCasesTest extends TestCase
{
    /**
     * Test that validation handles empty string values correctly.
     */
    public function test_validation_handles_empty_string_values_correctly(): void
    {
        // Empty strings should be treated as valid string values, not null
        $transition = new TransitionDefinition(
            fromState: '',
            toState: ''
        );

        $this->assertSame('', $transition->fromState);
        $this->assertSame('', $transition->toState);
    }

    /**
     * Test that validation handles empty string values in fromArray.
     */
    public function test_validation_handles_empty_string_values_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => '',
            'toState' => '',
        ]);

        $this->assertSame('', $transition->fromState);
        $this->assertSame('', $transition->toState);
    }

    /**
     * Test that validation distinguishes between null and empty string.
     */
    public function test_validation_distinguishes_between_null_and_empty_string(): void
    {
        // null values should be allowed for wildcard transitions
        $transition1 = new TransitionDefinition(
            fromState: null,
            toState: null
        );

        $this->assertNull($transition1->fromState);
        $this->assertNull($transition1->toState);
        $this->assertTrue($transition1->isWildcardTransition());

        // empty strings should be treated as valid string values
        $transition2 = new TransitionDefinition(
            fromState: '',
            toState: ''
        );

        $this->assertSame('', $transition2->fromState);
        $this->assertSame('', $transition2->toState);
        $this->assertFalse($transition2->isWildcardTransition());
    }

    /**
     * Test that validation handles whitespace-only strings correctly.
     */
    public function test_validation_handles_whitespace_only_strings_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: '   ',
            toState: '   '
        );

        $this->assertSame('   ', $transition->fromState);
        $this->assertSame('   ', $transition->toState);
    }

    /**
     * Test that validation handles whitespace-only strings in fromArray.
     */
    public function test_validation_handles_whitespace_only_strings_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => '   ',
            'toState' => '   ',
        ]);

        $this->assertSame('   ', $transition->fromState);
        $this->assertSame('   ', $transition->toState);
    }

    /**
     * Test that validation handles mixed null and string values correctly.
     */
    public function test_validation_handles_mixed_null_and_string_values_correctly(): void
    {
        $testCases = [
            ['fromState' => null, 'toState' => 'active'],
            ['fromState' => 'pending', 'toState' => null],
            ['fromState' => null, 'toState' => TestFeatureState::Active],
            ['fromState' => TestFeatureState::Pending, 'toState' => null],
        ];

        foreach ($testCases as $testCase) {
            $transition = new TransitionDefinition(
                fromState: $testCase['fromState'],
                toState: $testCase['toState']
            );

            $this->assertSame($testCase['fromState'], $transition->fromState);
            $this->assertSame($testCase['toState'], $transition->toState);
        }
    }

    /**
     * Test that validation handles mixed null and string values in fromArray.
     */
    public function test_validation_handles_mixed_null_and_string_values_in_from_array(): void
    {
        $testCases = [
            ['fromState' => null, 'toState' => 'active'],
            ['fromState' => 'pending', 'toState' => null],
            ['fromState' => null, 'toState' => TestFeatureState::Active],
            ['fromState' => TestFeatureState::Pending, 'toState' => null],
        ];

        foreach ($testCases as $testCase) {
            $transition = TransitionDefinition::fromArray($testCase);

            $this->assertSame($testCase['fromState'], $transition->fromState);
            $this->assertSame($testCase['toState'], $transition->toState);
        }
    }

    /**
     * Test that validation error messages are consistent between methods.
     */
    public function test_validation_error_messages_are_consistent_between_methods(): void
    {
        $invalidData = [
            'fromState' => 123,
            'toState' => TestFeatureState::Active,
        ];

        // Test constructor error message
        try {
            new TransitionDefinition(
                fromState: 123,
                toState: TestFeatureState::Active
            );
            $this->fail('Expected TypeError was not thrown');
        } catch (\TypeError $e) {
            // Constructor uses PHP's type system, so it throws TypeError
            $this->assertStringContainsString('must be of type', $e->getMessage());
        }

        // Test fromArray error message
        try {
            TransitionDefinition::fromArray($invalidData);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(
                'The "fromState" value must be a string, FsmStateEnum, or null, got: int',
                $e->getMessage()
            );
        }
    }

    /**
     * Test that validation handles array keys that don't exist.
     */
    public function test_validation_handles_missing_array_keys(): void
    {
        // Missing toState key should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'event' => 'test',
        ]);
    }

    /**
     * Test that validation handles non-associative arrays.
     */
    public function test_validation_handles_non_associative_arrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        TransitionDefinition::fromArray([
            TestFeatureState::Pending,
            TestFeatureState::Active,
        ]);
    }

    /**
     * Test that validation handles empty arrays.
     */
    public function test_validation_handles_empty_arrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array-based initialization requires an associative array with a "toState" or "to_state" key.');

        TransitionDefinition::fromArray([]);
    }

    /**
     * Test that validation handles arrays with only null values.
     */
    public function test_validation_handles_arrays_with_only_null_values(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that validation handles arrays with mixed key formats.
     */
    public function test_validation_handles_arrays_with_mixed_key_formats(): void
    {
        $testCases = [
            // camelCase
            ['fromState' => null, 'toState' => null],
            // snake_case
            ['from_state' => null, 'to_state' => null],
            // mixed
            ['fromState' => null, 'to_state' => null],
            ['from_state' => null, 'toState' => null],
        ];

        foreach ($testCases as $testCase) {
            $transition = TransitionDefinition::fromArray($testCase);

            $this->assertNull($transition->fromState);
            $this->assertNull($transition->toState);
            $this->assertTrue($transition->isWildcardTransition());
        }
    }

    /**
     * Test that validation handles arrays with extra keys.
     */
    public function test_validation_handles_arrays_with_extra_keys(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => null,
            'toState' => null,
            'extraKey' => 'extraValue',
            'anotherKey' => 123,
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that validation handles arrays with duplicate keys.
     */
    public function test_validation_handles_arrays_with_duplicate_keys(): void
    {
        // PHP arrays with duplicate keys will use the last value
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'fromState' => null, // This will override the previous value
            'toState' => TestFeatureState::Active,
            'toState' => null, // This will override the previous value
        ]);

        $this->assertNull($transition->fromState);
        $this->assertNull($transition->toState);
        $this->assertTrue($transition->isWildcardTransition());
    }

    /**
     * Test that validation handles arrays with both camelCase and snake_case for same property.
     */
    public function test_validation_handles_arrays_with_both_key_formats_for_same_property(): void
    {
        // When both camelCase and snake_case are present, camelCase should take precedence
        $transition = TransitionDefinition::fromArray([
            'fromState' => TestFeatureState::Pending,
            'from_state' => null, // This should be ignored
            'toState' => TestFeatureState::Active,
            'to_state' => null, // This should be ignored
        ]);

        $this->assertSame(TestFeatureState::Pending, $transition->fromState);
        $this->assertSame(TestFeatureState::Active, $transition->toState);
    }

    /**
     * Test that validation handles very long string values.
     */
    public function test_validation_handles_very_long_string_values(): void
    {
        $longString = str_repeat('a', 10000);

        $transition = new TransitionDefinition(
            fromState: $longString,
            toState: $longString
        );

        $this->assertSame($longString, $transition->fromState);
        $this->assertSame($longString, $transition->toState);
    }

    /**
     * Test that validation handles very long string values in fromArray.
     */
    public function test_validation_handles_very_long_string_values_in_from_array(): void
    {
        $longString = str_repeat('a', 10000);

        $transition = TransitionDefinition::fromArray([
            'fromState' => $longString,
            'toState' => $longString,
        ]);

        $this->assertSame($longString, $transition->fromState);
        $this->assertSame($longString, $transition->toState);
    }

    /**
     * Test that validation handles special characters in string values.
     */
    public function test_validation_handles_special_characters_in_string_values(): void
    {
        $specialString = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $transition = new TransitionDefinition(
            fromState: $specialString,
            toState: $specialString
        );

        $this->assertSame($specialString, $transition->fromState);
        $this->assertSame($specialString, $transition->toState);
    }

    /**
     * Test that validation handles special characters in fromArray.
     */
    public function test_validation_handles_special_characters_in_from_array(): void
    {
        $specialString = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $transition = TransitionDefinition::fromArray([
            'fromState' => $specialString,
            'toState' => $specialString,
        ]);

        $this->assertSame($specialString, $transition->fromState);
        $this->assertSame($specialString, $transition->toState);
    }

    /**
     * Test that validation handles unicode characters in string values.
     */
    public function test_validation_handles_unicode_characters_in_string_values(): void
    {
        $unicodeString = '🚀🌟✨🎉🎊';

        $transition = new TransitionDefinition(
            fromState: $unicodeString,
            toState: $unicodeString
        );

        $this->assertSame($unicodeString, $transition->fromState);
        $this->assertSame($unicodeString, $transition->toState);
    }

    /**
     * Test that validation handles unicode characters in fromArray.
     */
    public function test_validation_handles_unicode_characters_in_from_array(): void
    {
        $unicodeString = '🚀🌟✨🎉🎊';

        $transition = TransitionDefinition::fromArray([
            'fromState' => $unicodeString,
            'toState' => $unicodeString,
        ]);

        $this->assertSame($unicodeString, $transition->fromState);
        $this->assertSame($unicodeString, $transition->toState);
    }

    /**
     * Test that validation handles numeric strings correctly.
     */
    public function test_validation_handles_numeric_strings_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: '123',
            toState: '456'
        );

        $this->assertSame('123', $transition->fromState);
        $this->assertSame('456', $transition->toState);
    }

    /**
     * Test that validation handles numeric strings in fromArray.
     */
    public function test_validation_handles_numeric_strings_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => '123',
            'toState' => '456',
        ]);

        $this->assertSame('123', $transition->fromState);
        $this->assertSame('456', $transition->toState);
    }

    /**
     * Test that validation handles boolean-like strings correctly.
     */
    public function test_validation_handles_boolean_like_strings_correctly(): void
    {
        $transition = new TransitionDefinition(
            fromState: 'true',
            toState: 'false'
        );

        $this->assertSame('true', $transition->fromState);
        $this->assertSame('false', $transition->toState);
    }

    /**
     * Test that validation handles boolean-like strings in fromArray.
     */
    public function test_validation_handles_boolean_like_strings_in_from_array(): void
    {
        $transition = TransitionDefinition::fromArray([
            'fromState' => 'true',
            'toState' => 'false',
        ]);

        $this->assertSame('true', $transition->fromState);
        $this->assertSame('false', $transition->toState);
    }
}
