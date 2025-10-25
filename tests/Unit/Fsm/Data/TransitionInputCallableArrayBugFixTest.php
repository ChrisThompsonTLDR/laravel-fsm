<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Data;

use Fsm\Data\TransitionInput;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class TransitionInputCallableArrayBugFixTest extends TestCase
{
    /**
     * Test that TransitionInput constructor handles array-based construction correctly.
     * Note: TransitionInput takes Model|array as first parameter, not callable,
     * but we should ensure it doesn't have similar issues.
     */
    public function test_array_based_construction_works_correctly(): void
    {
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'fromState' => 'pending',
            'toState' => 'approved',
            'context' => null,
            'event' => 'approve',
            'isDryRun' => false,
            'mode' => TransitionInput::MODE_NORMAL,
            'source' => TransitionInput::SOURCE_USER,
            'metadata' => [],
            'timestamp' => null,
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('approved', $input->toState);
        $this->assertEquals('approve', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals([], $input->metadata);
    }

    /**
     * Test that TransitionInput constructor handles snake_case keys correctly.
     */
    public function test_snake_case_keys_are_normalized_correctly(): void
    {
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'from_state' => 'pending', // snake_case
            'to_state' => 'approved', // snake_case
            'context' => null,
            'event' => 'approve',
            'is_dry_run' => false, // snake_case
            'mode' => TransitionInput::MODE_NORMAL,
            'source' => TransitionInput::SOURCE_USER,
            'metadata' => [],
            'timestamp' => null,
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertEquals('pending', $input->fromState); // Should be normalized to camelCase
        $this->assertEquals('approved', $input->toState); // Should be normalized to camelCase
        $this->assertEquals('approve', $input->event);
        $this->assertFalse($input->isDryRun); // Should be normalized to camelCase
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals([], $input->metadata);
    }

    /**
     * Test that positional parameters work correctly.
     */
    public function test_positional_parameters_work_correctly(): void
    {
        $model = $this->createMock(Model::class);

        $input = new TransitionInput(
            $model,
            'pending',
            'approved',
            null,
            'approve',
            false,
            TransitionInput::MODE_NORMAL,
            TransitionInput::SOURCE_USER,
            [],
            null
        );

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertSame($model, $input->model);
        $this->assertEquals('pending', $input->fromState);
        $this->assertEquals('approved', $input->toState);
        $this->assertNull($input->context);
        $this->assertEquals('approve', $input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals([], $input->metadata);
        $this->assertNull($input->timestamp);
    }

    /**
     * Test that non-associative arrays are rejected for array-based construction.
     */
    public function test_non_associative_arrays_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // This should fail - non-associative array
        new TransitionInput(['pending', 'approved', 'approve']);
    }

    /**
     * Test that arrays without required keys are handled appropriately.
     */
    public function test_arrays_without_required_keys_are_handled_appropriately(): void
    {
        // This should work - missing optional keys should use defaults
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'fromState' => null, // Explicitly set to null
            'toState' => 'approved', // Only required key for normal mode
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertNull($input->fromState);
        $this->assertEquals('approved', $input->toState);
        $this->assertNull($input->context);
        $this->assertNull($input->event);
        $this->assertFalse($input->isDryRun);
        $this->assertEquals(TransitionInput::MODE_NORMAL, $input->mode);
        $this->assertEquals(TransitionInput::SOURCE_USER, $input->source);
        $this->assertEquals([], $input->metadata);
        $this->assertNull($input->timestamp);
    }

    /**
     * Test that missing toState in normal mode throws exception.
     */
    public function test_missing_tostate_in_normal_mode_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TransitionInput requires a non-null "toState" or "to_state" value for normal mode transitions.');

        new TransitionInput([
            'model' => $this->createMock(Model::class),
            // Missing toState
        ]);
    }

    /**
     * Test that missing toState in dry run mode is allowed.
     */
    public function test_missing_tostate_in_dry_run_mode_is_allowed(): void
    {
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'mode' => TransitionInput::MODE_DRY_RUN,
            // Missing toState - should be allowed in dry run mode
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_DRY_RUN, $input->mode);
    }

    /**
     * Test that missing toState in force mode is allowed.
     */
    public function test_missing_tostate_in_force_mode_is_allowed(): void
    {
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'mode' => TransitionInput::MODE_FORCE,
            // Missing toState - should be allowed in force mode
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_FORCE, $input->mode);
    }

    /**
     * Test that missing toState in silent mode is allowed.
     */
    public function test_missing_tostate_in_silent_mode_is_allowed(): void
    {
        $input = new TransitionInput([
            'model' => $this->createMock(Model::class),
            'mode' => TransitionInput::MODE_SILENT,
            // Missing toState - should be allowed in silent mode
        ]);

        $this->assertInstanceOf(TransitionInput::class, $input);
        $this->assertNull($input->toState);
        $this->assertEquals(TransitionInput::MODE_SILENT, $input->mode);
    }
}
