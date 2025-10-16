<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Fsm\Data\ReplayHistoryResponse;
use Fsm\Data\StateDefinition;
use Fsm\Data\StateTimeAnalysisData;
use Fsm\Data\StateTimelineEntryData;
use Fsm\Data\TransitionDefinition;
use Illuminate\Support\Collection;

describe('DTO Constructor Type Safety', function () {
    describe('StateTimelineEntryData', function () {
        it('handles positional parameters correctly', function () {
            $dto = new StateTimelineEntryData(
                id: 'test-id',
                model_id: 'model-123',
                model_type: 'TestModel',
                fsm_column: 'status',
                from_state: 'pending',
                to_state: 'active',
                transition_event: 'activate',
                context_snapshot: ['key' => 'value'],
                exception_details: null,
                duration_ms: 1000,
                happened_at: CarbonImmutable::now(),
                subject_id: 'user-123',
                subject_type: 'User'
            );

            expect($dto->id)->toBe('test-id');
            expect($dto->modelId)->toBe('model-123');
            expect($dto->modelType)->toBe('TestModel');
            expect($dto->fsmColumn)->toBe('status');
            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBe('active');
            expect($dto->transitionEvent)->toBe('activate');
            expect($dto->contextSnapshot)->toBe(['key' => 'value']);
            expect($dto->exceptionDetails)->toBeNull();
            expect($dto->durationMs)->toBe(1000);
            expect($dto->happenedAt)->toBeInstanceOf(CarbonImmutable::class);
            expect($dto->subjectId)->toBe('user-123');
            expect($dto->subjectType)->toBe('User');
        });

        it('handles array-based construction correctly', function () {
            $data = [
                'id' => 'test-id',
                'model_id' => 'model-123',
                'model_type' => 'TestModel',
                'fsm_column' => 'status',
                'from_state' => 'pending',
                'to_state' => 'active',
                'transition_event' => 'activate',
                'context_snapshot' => ['key' => 'value'],
                'exception_details' => null,
                'duration_ms' => 1000,
                'happened_at' => CarbonImmutable::now(),
                'subject_id' => 'user-123',
                'subject_type' => 'User',
            ];

            $dto = new StateTimelineEntryData($data);

            expect($dto->id)->toBe('test-id');
            expect($dto->modelId)->toBe('model-123');
            expect($dto->modelType)->toBe('TestModel');
            expect($dto->fsmColumn)->toBe('status');
            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBe('active');
            expect($dto->transitionEvent)->toBe('activate');
            expect($dto->contextSnapshot)->toBe(['key' => 'value']);
            expect($dto->exceptionDetails)->toBeNull();
            expect($dto->durationMs)->toBe(1000);
            expect($dto->happenedAt)->toBeInstanceOf(CarbonImmutable::class);
            expect($dto->subjectId)->toBe('user-123');
            expect($dto->subjectType)->toBe('User');
        });

        it('rejects non-associative arrays', function () {
            expect(fn () => new StateTimelineEntryData(['value1', 'value2']))
                ->toThrow(InvalidArgumentException::class, 'Array-based initialization requires an associative array.');
        });

        it('validates required keys in array construction', function () {
            expect(fn () => new StateTimelineEntryData(['model_id' => 'test']))
                ->toThrow(InvalidArgumentException::class, 'Missing required keys for array construction: id');
        });

        it('handles minimal positional parameters', function () {
            $dto = new StateTimelineEntryData('minimal-id');

            expect($dto->id)->toBe('minimal-id');
            expect($dto->modelId)->toBeNull();
            expect($dto->modelType)->toBeNull();
            expect($dto->fsmColumn)->toBeNull();
            expect($dto->fromState)->toBeNull();
            expect($dto->toState)->toBeNull();
            expect($dto->transitionEvent)->toBeNull();
            expect($dto->contextSnapshot)->toBeNull();
            expect($dto->exceptionDetails)->toBeNull();
            expect($dto->durationMs)->toBeNull();
            expect($dto->happenedAt)->toBeNull();
            expect($dto->subjectId)->toBeNull();
            expect($dto->subjectType)->toBeNull();
        });
    });

    describe('TransitionDefinition', function () {
        it('handles positional parameters correctly', function () {
            $dto = new TransitionDefinition(
                fromState: 'pending',
                toState: 'active',
                event: 'activate',
                guards: [],
                actions: [],
                onTransitionCallbacks: [],
                description: 'Test transition',
                type: TransitionDefinition::TYPE_MANUAL,
                priority: TransitionDefinition::PRIORITY_HIGH,
                behavior: TransitionDefinition::BEHAVIOR_IMMEDIATE,
                guardEvaluation: TransitionDefinition::GUARD_EVALUATION_ALL,
                metadata: ['key' => 'value'],
                isReversible: true,
                timeout: 60
            );

            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBe('active');
            expect($dto->event)->toBe('activate');
            expect($dto->guards)->toBeInstanceOf(Collection::class);
            expect($dto->actions)->toBeInstanceOf(Collection::class);
            expect($dto->onTransitionCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBe('Test transition');
            expect($dto->type)->toBe(TransitionDefinition::TYPE_MANUAL);
            expect($dto->priority)->toBe(TransitionDefinition::PRIORITY_HIGH);
            expect($dto->behavior)->toBe(TransitionDefinition::BEHAVIOR_IMMEDIATE);
            expect($dto->guardEvaluation)->toBe(TransitionDefinition::GUARD_EVALUATION_ALL);
            expect($dto->metadata)->toBe(['key' => 'value']);
            expect($dto->isReversible)->toBeTrue();
            expect($dto->timeout)->toBe(60);
        });

        it('handles array-based construction correctly', function () {
            $data = [
                'from_state' => 'pending',
                'to_state' => 'active',
                'event' => 'activate',
                'guards' => [],
                'actions' => [],
                'on_transition_callbacks' => [],
                'description' => 'Test transition',
                'type' => TransitionDefinition::TYPE_MANUAL,
                'priority' => TransitionDefinition::PRIORITY_HIGH,
                'behavior' => TransitionDefinition::BEHAVIOR_IMMEDIATE,
                'guard_evaluation' => TransitionDefinition::GUARD_EVALUATION_ALL,
                'metadata' => ['key' => 'value'],
                'is_reversible' => true,
                'timeout' => 60,
            ];

            $dto = new TransitionDefinition($data);

            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBe('active');
            expect($dto->event)->toBe('activate');
            expect($dto->guards)->toBeInstanceOf(Collection::class);
            expect($dto->actions)->toBeInstanceOf(Collection::class);
            expect($dto->onTransitionCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBe('Test transition');
            expect($dto->type)->toBe(TransitionDefinition::TYPE_MANUAL);
            expect($dto->priority)->toBe(TransitionDefinition::PRIORITY_HIGH);
            expect($dto->behavior)->toBe(TransitionDefinition::BEHAVIOR_IMMEDIATE);
            expect($dto->guardEvaluation)->toBe(TransitionDefinition::GUARD_EVALUATION_ALL);
            expect($dto->metadata)->toBe(['key' => 'value']);
            expect($dto->isReversible)->toBeTrue();
            expect($dto->timeout)->toBe(60);
        });

        it('rejects non-associative arrays', function () {
            expect(fn () => new TransitionDefinition(['value1', 'value2']))
                ->toThrow(InvalidArgumentException::class, 'Array-based initialization requires an associative array with a "toState" or "to_state" key.');
        });

        it('validates toState requirement in array construction', function () {
            expect(fn () => new TransitionDefinition(['from_state' => 'pending']))
                ->toThrow(InvalidArgumentException::class, 'Array-based initialization requires an associative array with a "toState" or "to_state" key.');
        });

        it('allows toState to be null for wildcard transitions in array construction', function () {
            $dto = new TransitionDefinition(['to_state' => null]);
            expect($dto->toState)->toBeNull();
            expect($dto->isWildcardTransition())->toBeTrue();
        });

        it('allows toState to be null for wildcard transitions in positional construction', function () {
            $dto = new TransitionDefinition(fromState: 'pending', toState: null);
            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBeNull();
        });

        it('handles minimal positional parameters', function () {
            $dto = new TransitionDefinition(
                fromState: 'pending',
                toState: 'active'
            );

            expect($dto->fromState)->toBe('pending');
            expect($dto->toState)->toBe('active');
            expect($dto->event)->toBeNull();
            expect($dto->guards)->toBeInstanceOf(Collection::class);
            expect($dto->actions)->toBeInstanceOf(Collection::class);
            expect($dto->onTransitionCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBeNull();
            expect($dto->type)->toBe(TransitionDefinition::TYPE_MANUAL);
            expect($dto->priority)->toBe(TransitionDefinition::PRIORITY_NORMAL);
            expect($dto->behavior)->toBe(TransitionDefinition::BEHAVIOR_IMMEDIATE);
            expect($dto->guardEvaluation)->toBe(TransitionDefinition::GUARD_EVALUATION_ALL);
            expect($dto->metadata)->toBe([]);
            expect($dto->isReversible)->toBeFalse();
            expect($dto->timeout)->toBe(30);
        });
    });

    describe('StateDefinition', function () {
        it('handles positional parameters correctly', function () {
            $dto = new StateDefinition(
                name: 'active',
                onEntryCallbacks: [],
                onExitCallbacks: [],
                description: 'Active state',
                type: StateDefinition::TYPE_INTERMEDIATE,
                category: StateDefinition::CATEGORY_ACTIVE,
                behavior: StateDefinition::BEHAVIOR_PERSISTENT,
                metadata: ['key' => 'value'],
                isTerminal: false,
                priority: 75
            );

            expect($dto->name)->toBe('active');
            expect($dto->onEntryCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->onExitCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBe('Active state');
            expect($dto->type)->toBe(StateDefinition::TYPE_INTERMEDIATE);
            expect($dto->category)->toBe(StateDefinition::CATEGORY_ACTIVE);
            expect($dto->behavior)->toBe(StateDefinition::BEHAVIOR_PERSISTENT);
            expect($dto->metadata)->toBe(['key' => 'value']);
            expect($dto->isTerminal)->toBeFalse();
            expect($dto->priority)->toBe(75);
        });

        it('handles array-based construction correctly', function () {
            $data = [
                'name' => 'active',
                'on_entry_callbacks' => [],
                'on_exit_callbacks' => [],
                'description' => 'Active state',
                'type' => StateDefinition::TYPE_INTERMEDIATE,
                'category' => StateDefinition::CATEGORY_ACTIVE,
                'behavior' => StateDefinition::BEHAVIOR_PERSISTENT,
                'metadata' => ['key' => 'value'],
                'is_terminal' => false,
                'priority' => 75,
            ];

            $dto = new StateDefinition($data);

            expect($dto->name)->toBe('active');
            expect($dto->onEntryCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->onExitCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBe('Active state');
            expect($dto->type)->toBe(StateDefinition::TYPE_INTERMEDIATE);
            expect($dto->category)->toBe(StateDefinition::CATEGORY_ACTIVE);
            expect($dto->behavior)->toBe(StateDefinition::BEHAVIOR_PERSISTENT);
            expect($dto->metadata)->toBe(['key' => 'value']);
            expect($dto->isTerminal)->toBeFalse();
            expect($dto->priority)->toBe(75);
        });

        it('rejects non-associative arrays', function () {
            expect(fn () => new StateDefinition(['value1', 'value2']))
                ->toThrow(InvalidArgumentException::class, 'Array-based initialization requires an associative array.');
        });

        it('handles minimal positional parameters', function () {
            $dto = new StateDefinition('minimal');

            expect($dto->name)->toBe('minimal');
            expect($dto->onEntryCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->onExitCallbacks)->toBeInstanceOf(Collection::class);
            expect($dto->description)->toBeNull();
            expect($dto->type)->toBe(StateDefinition::TYPE_INTERMEDIATE);
            expect($dto->category)->toBeNull();
            expect($dto->behavior)->toBe(StateDefinition::BEHAVIOR_PERSISTENT);
            expect($dto->metadata)->toBe([]);
            expect($dto->isTerminal)->toBeFalse();
            expect($dto->priority)->toBe(50);
        });
    });

    describe('StateTimeAnalysisData', function () {
        it('handles positional parameters correctly', function () {
            $dto = new StateTimeAnalysisData(
                state: 'active',
                totalDurationMs: 5000,
                occurrenceCount: 3,
                averageDurationMs: 1666.67,
                minDurationMs: 1000,
                maxDurationMs: 2000
            );

            expect($dto->state)->toBe('active');
            expect($dto->totalDurationMs)->toBe(5000);
            expect($dto->occurrenceCount)->toBe(3);
            expect($dto->averageDurationMs)->toBe(1666.67);
            expect($dto->minDurationMs)->toBe(1000);
            expect($dto->maxDurationMs)->toBe(2000);
        });

        it('handles array-based construction correctly', function () {
            $data = [
                'state' => 'active',
                'totalDurationMs' => 5000,
                'occurrenceCount' => 3,
                'averageDurationMs' => 1666.67,
                'minDurationMs' => 1000,
                'maxDurationMs' => 2000,
            ];

            $dto = new StateTimeAnalysisData($data);

            expect($dto->state)->toBe('active');
            expect($dto->totalDurationMs)->toBe(5000);
            expect($dto->occurrenceCount)->toBe(3);
            expect($dto->averageDurationMs)->toBe(1666.67);
            expect($dto->minDurationMs)->toBe(1000);
            expect($dto->maxDurationMs)->toBe(2000);
        });

        it('rejects non-associative arrays', function () {
            expect(fn () => new StateTimeAnalysisData(['value1', 'value2']))
                ->toThrow(InvalidArgumentException::class, 'Array-based initialization requires an associative array.');
        });

        it('validates required keys in array construction', function () {
            expect(fn () => new StateTimeAnalysisData(['state' => 'active']))
                ->toThrow(InvalidArgumentException::class, 'Missing required keys in StateTimeAnalysisData: totalDurationMs, occurrenceCount, averageDurationMs');
        });

        it('handles minimal positional parameters', function () {
            $dto = new StateTimeAnalysisData('minimal');

            expect($dto->state)->toBe('minimal');
            expect($dto->totalDurationMs)->toBe(0);
            expect($dto->occurrenceCount)->toBe(0);
            expect($dto->averageDurationMs)->toBe(0.0);
            expect($dto->minDurationMs)->toBeNull();
            expect($dto->maxDurationMs)->toBeNull();
        });
    });

    describe('ReplayHistoryResponse', function () {
        it('handles positional parameters correctly', function () {
            $dto = new ReplayHistoryResponse(
                success: true,
                data: ['key' => 'value'],
                message: 'Success message',
                error: null,
                details: ['detail' => 'info']
            );

            expect($dto->success)->toBeTrue();
            expect($dto->data)->toBe(['key' => 'value']);
            expect($dto->message)->toBe('Success message');
            expect($dto->error)->toBeNull();
            expect($dto->details)->toBe(['detail' => 'info']);
        });

        it('handles array-based construction correctly', function () {
            $data = [
                'success' => true,
                'data' => ['key' => 'value'],
                'message' => 'Success message',
                'error' => null,
                'details' => ['detail' => 'info'],
            ];

            $dto = new ReplayHistoryResponse($data);

            expect($dto->success)->toBeTrue();
            expect($dto->data)->toBe(['key' => 'value']);
            expect($dto->message)->toBe('Success message');
            expect($dto->error)->toBeNull();
            expect($dto->details)->toBe(['detail' => 'info']);
        });

        it('rejects non-associative arrays', function () {
            expect(fn () => new ReplayHistoryResponse(['value1', 'value2']))
                ->toThrow(InvalidArgumentException::class, 'Array-based construction cannot use callable arrays.');
        });

        it('handles minimal positional parameters', function () {
            $dto = new ReplayHistoryResponse(success: false);

            expect($dto->success)->toBeFalse();
            expect($dto->data)->toBe([]);
            expect($dto->message)->toBe('');
            expect($dto->error)->toBeNull();
            expect($dto->details)->toBeNull();
        });

        it('handles error response', function () {
            $dto = new ReplayHistoryResponse(
                success: false,
                data: [],
                message: 'Error occurred',
                error: 'Something went wrong',
                details: ['code' => 500]
            );

            expect($dto->success)->toBeFalse();
            expect($dto->data)->toBe([]);
            expect($dto->message)->toBe('Error occurred');
            expect($dto->error)->toBe('Something went wrong');
            expect($dto->details)->toBe(['code' => 500]);
        });
    });

    describe('Type Safety Edge Cases', function () {
        it('ensures array type is only used for detection, not as valid parameter type', function () {
            // These should all work without type errors
            $timeline = new StateTimelineEntryData('string-id');
            $transition = new TransitionDefinition('from', 'to');
            $state = new StateDefinition('state-name');
            $analysis = new StateTimeAnalysisData('state');
            $response = new ReplayHistoryResponse(true);

            expect($timeline->id)->toBe('string-id');
            expect($transition->fromState)->toBe('from');
            expect($transition->toState)->toBe('to');
            expect($state->name)->toBe('state-name');
            expect($analysis->state)->toBe('state');
            expect($response->success)->toBeTrue();
        });

        it('handles mixed parameter scenarios correctly', function () {
            // Test that positional parameters work with various types
            $timeline = new StateTimelineEntryData(
                id: 'test',
                model_id: 'model-123',
                duration_ms: 1000,
                happened_at: CarbonImmutable::now()
            );

            expect($timeline->id)->toBe('test');
            expect($timeline->modelId)->toBe('model-123');
            expect($timeline->durationMs)->toBe(1000);
            expect($timeline->happenedAt)->toBeInstanceOf(CarbonImmutable::class);
        });

        it('maintains backward compatibility with array construction', function () {
            // Test that existing array-based construction still works
            $data = [
                'id' => 'test-id',
                'model_id' => 'model-123',
                'from_state' => 'pending',
                'to_state' => 'active',
            ];

            $timeline = new StateTimelineEntryData($data);

            expect($timeline->id)->toBe('test-id');
            expect($timeline->modelId)->toBe('model-123');
            expect($timeline->fromState)->toBe('pending');
            expect($timeline->toState)->toBe('active');
        });
    });
});
