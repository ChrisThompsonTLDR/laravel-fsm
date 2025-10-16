<?php

declare(strict_types=1);

namespace Tests\Integration\Fsm;

use Fsm\Data\TransitionInput;
use Fsm\TransitionBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Integration test demonstrating a complete FSM workflow with all
 * action and callback functionality working together.
 *
 * This test validates that the comprehensive action/callback system
 * works end-to-end with:
 * - Multiple action types and timing
 * - State-level callbacks
 * - Error handling and success patterns
 * - Priority-based execution
 * - Queue support
 * - Complex workflows
 */
class ComprehensiveWorkflowIntegrationTest extends TestCase
{
    private array $executionLog = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->executionLog = [];
    }

    #[Test]
    public function comprehensive_order_workflow_demonstrates_all_functionality(): void
    {
        $builder = TransitionBuilder::for('TestOrder', 'status')
            ->initialState('pending')

            // Define states with comprehensive callbacks
            ->state('pending', function ($builder) {
                $builder->onEntry(function (TransitionInput $input) {
                    $this->log('state:pending:entry', $input);
                });
                $builder->onExit(function (TransitionInput $input) {
                    $this->log('state:pending:exit', $input);
                });
            })

            ->state('processing', function ($builder) {
                $builder->onEntry(function (TransitionInput $input) {
                    $this->log('state:processing:entry', $input);
                }, [], false, true); // queued entry callback

                $builder->onExit(function (TransitionInput $input) {
                    $this->log('state:processing:exit', $input);
                });
            })

            ->state('completed', function ($builder) {
                $builder->onEntry(function (TransitionInput $input) {
                    $this->log('state:completed:entry', $input);
                });
            })

            ->state('failed', function ($builder) {
                $builder->onEntry(function (TransitionInput $input) {
                    $this->log('state:failed:entry', $input);
                });
            })

            // Transition 1: pending -> processing (demonstrates all callback types)
            ->transition('Order Processing Workflow')
            ->from('pending')
            ->to('processing')
            ->event('start_processing')

            // Pre-transition validation
            ->before(function (TransitionInput $input) {
                $this->log('callback:before:validation', $input);

                return $input->context['valid'] ?? true;
            })

            // Immediate high-priority action
            ->immediateAction(function (TransitionInput $input) {
                $this->log('action:immediate:reserve_resources', $input);
            }, [], 'Reserve resources immediately')

            // Regular action after state change
            ->action(function (TransitionInput $input) {
                $this->log('action:regular:process_order', $input);
            }, [], false, 'Process order')

            // Queued action for heavy operations
            ->queuedAction(function (TransitionInput $input) {
                $this->log('action:queued:sync_external', $input);
            }, [], 'Sync with external systems')

            // Post-transition callback
            ->after(function (TransitionInput $input) {
                $this->log('callback:after:finalize', $input);
            })

            // Success handler
            ->onSuccess(function (TransitionInput $input) {
                $this->log('handler:success:processing_started', $input);
            })

            // Failure handler
            ->onFailure(function (TransitionInput $input) {
                $this->log('handler:failure:processing_failed', $input);
            })

            // Notification
            ->notify(function (TransitionInput $input) {
                $this->log('helper:notify:customer', $input);
            })

            // Logging
            ->log(function (TransitionInput $input) {
                $this->log('helper:log:transition', $input);
            })

            // Cleanup
            ->cleanup(function (TransitionInput $input) {
                $this->log('helper:cleanup:temp_resources', $input);
            })

            // Transition 2: processing -> completed (success path)
            ->transition('Order Completion')
            ->from('processing')
            ->to('completed')
            ->event('complete')

            ->guard(function (TransitionInput $input) {
                $this->log('guard:completion_check', $input);

                return $input->context['can_complete'] ?? true;
            })

            ->before([self::class, 'validateCompletion'])
            ->action([self::class, 'processCompletion'])
            ->after([self::class, 'finalizeCompletion'])
            ->onSuccess([self::class, 'handleCompletionSuccess'])
            ->notify([self::class, 'sendCompletionNotification'])
            ->cleanup([self::class, 'cleanupCompletion'])

            // Transition 3: processing -> failed (failure path)
            ->transition('Order Failure')
            ->from('processing')
            ->to('failed')
            ->event('fail')

            ->immediateAction([self::class, 'stopProcessingImmediately'])
            ->action([self::class, 'reverseProcessing'])
            ->queuedAction([self::class, 'processFailureRefund'])
            ->notify([self::class, 'sendFailureNotification'])
            ->onFailure([self::class, 'handleFailureEscalation'])
            ->cleanup([self::class, 'cleanupFailure']);

        $runtimeDefinition = $builder->buildRuntimeDefinition();

        // Verify the FSM structure
        $this->assertNotNull($runtimeDefinition);
        $this->assertEquals('pending', $runtimeDefinition->initialState);
        $this->assertCount(4, $runtimeDefinition->stateDefinitions);
        $this->assertCount(3, $runtimeDefinition->transitionDefinitions);

        // Verify first transition has comprehensive actions/callbacks
        $processingTransition = $runtimeDefinition->transitionDefinitions[0];
        $this->assertEquals('Order Processing Workflow', $processingTransition->description);

        // Should have all action types: immediate, regular, queued, success, failure, notify, cleanup
        $this->assertCount(7, $processingTransition->actions);

        // Should have before, after, and log callbacks
        $this->assertCount(3, $processingTransition->onTransitionCallbacks);

        // Verify action types and properties
        $actions = $processingTransition->actions->toArray();

        // Find immediate action
        $immediateActions = array_filter($actions, fn ($a) => $a->timing === 'before');
        $this->assertCount(1, $immediateActions);
        $immediateAction = array_values($immediateActions)[0];
        $this->assertEquals('Reserve resources immediately', $immediateAction->name);
        $this->assertEquals(75, $immediateAction->priority); // HIGH priority

        // Find queued actions
        $queuedActions = array_filter($actions, fn ($a) => $a->queued === true);
        $this->assertGreaterThanOrEqual(2, count($queuedActions)); // At least queued action + notify

        // Find cleanup action
        $cleanupActions = array_filter($actions, fn ($a) => $a->priority === 25); // LOW priority
        $this->assertCount(1, $cleanupActions);
        $cleanupAction = array_values($cleanupActions)[0];
        $this->assertEquals('Cleanup action', $cleanupAction->name);

        // Verify callbacks
        $callbacks = $processingTransition->onTransitionCallbacks->toArray();
        $beforeCallbacks = array_filter($callbacks, fn ($c) => ! $c->runAfterTransition);
        $afterCallbacks = array_filter($callbacks, fn ($c) => $c->runAfterTransition);

        $this->assertCount(1, $beforeCallbacks);
        $this->assertCount(2, $afterCallbacks); // after + log

        // Verify state definitions have callbacks
        $pendingState = array_filter($runtimeDefinition->stateDefinitions, fn ($s) => $s->name === 'pending')[0];
        $this->assertCount(1, $pendingState->onEntryCallbacks);
        $this->assertCount(1, $pendingState->onExitCallbacks);

        $processingState = array_filter($runtimeDefinition->stateDefinitions, fn ($s) => $s->name === 'processing')[0];
        $this->assertCount(1, $processingState->onEntryCallbacks);
        $this->assertCount(1, $processingState->onExitCallbacks);

        // Verify queued state callback
        $processingEntryCallback = $processingState->onEntryCallbacks[0];
        $this->assertTrue($processingEntryCallback->queued);
    }

    #[Test]
    public function action_parameters_are_preserved(): void
    {
        $builder = TransitionBuilder::for('TestModel', 'status')
            ->from('a')
            ->to('b')
            ->action(fn () => 'test', ['param1' => 'value1', 'param2' => 42])
            ->before(fn () => 'test', ['param3' => true])
            ->queuedAction(fn () => 'test', ['param4' => ['nested' => 'value']]);

        $transition = $builder->getTransitionDefinitions()[0];

        // Verify action parameters
        $action = $transition->actions[0];
        $this->assertEquals(['param1' => 'value1', 'param2' => 42], $action->parameters);

        // Verify callback parameters
        $callback = $transition->onTransitionCallbacks[0];
        $this->assertEquals(['param3' => true], $callback->parameters);

        // Verify queued action parameters
        $queuedAction = array_filter($transition->actions->toArray(), fn ($a) => $a->queued)[0];
        $this->assertEquals(['param4' => ['nested' => 'value']], $queuedAction->parameters);
    }

    #[Test]
    public function multiple_transitions_can_have_different_action_patterns(): void
    {
        $builder = TransitionBuilder::for('TestModel', 'status')
            // Simple transition with basic actions
            ->from('a')->to('b')
            ->action(fn () => 'simple action')
            ->after(fn () => 'simple callback')

            // Complex transition with all features
            ->transition('Complex workflow')
            ->from('b')->to('c')
            ->before(fn () => 'complex before')
            ->immediateAction(fn () => 'complex immediate')
            ->action(fn () => 'complex action')
            ->queuedAction(fn () => 'complex queued')
            ->after(fn () => 'complex after')
            ->onSuccess(fn () => 'complex success')
            ->onFailure(fn () => 'complex failure')
            ->notify(fn () => 'complex notify')
            ->log(fn () => 'complex log')
            ->cleanup(fn () => 'complex cleanup')

            // Minimal transition with just guards
            ->from('c')->to('d')
            ->guard(fn () => true);

        $transitions = $builder->getTransitionDefinitions();
        $this->assertCount(3, $transitions);

        // First transition - simple
        $this->assertCount(1, $transitions[0]->actions);
        $this->assertCount(1, $transitions[0]->onTransitionCallbacks);
        $this->assertNull($transitions[0]->description);

        // Second transition - complex
        $this->assertCount(7, $transitions[1]->actions); // immediate, action, queued, success, failure, notify, cleanup
        $this->assertCount(3, $transitions[1]->onTransitionCallbacks); // before, after, log
        $this->assertEquals('Complex workflow', $transitions[1]->description);

        // Third transition - minimal
        $this->assertCount(0, $transitions[2]->actions);
        $this->assertCount(0, $transitions[2]->onTransitionCallbacks);
        $this->assertCount(1, $transitions[2]->guards);
    }

    // Helper methods for testing

    private function log(string $type, TransitionInput $input): void
    {
        $this->executionLog[] = [
            'type' => $type,
            'from' => $input->fromState,
            'to' => $input->toState,
            'event' => $input->event,
            'timestamp' => microtime(true),
        ];
    }

    public function validateCompletion(TransitionInput $input): void
    {
        $this->log('static:validate_completion', $input);
    }

    public function processCompletion(TransitionInput $input): void
    {
        $this->log('static:process_completion', $input);
    }

    public function finalizeCompletion(TransitionInput $input): void
    {
        $this->log('static:finalize_completion', $input);
    }

    public function handleCompletionSuccess(TransitionInput $input): void
    {
        $this->log('static:completion_success', $input);
    }

    public function sendCompletionNotification(TransitionInput $input): void
    {
        $this->log('static:completion_notification', $input);
    }

    public function cleanupCompletion(TransitionInput $input): void
    {
        $this->log('static:completion_cleanup', $input);
    }

    public function stopProcessingImmediately(TransitionInput $input): void
    {
        $this->log('static:stop_processing', $input);
    }

    public function reverseProcessing(TransitionInput $input): void
    {
        $this->log('static:reverse_processing', $input);
    }

    public function processFailureRefund(TransitionInput $input): void
    {
        $this->log('static:failure_refund', $input);
    }

    public function sendFailureNotification(TransitionInput $input): void
    {
        $this->log('static:failure_notification', $input);
    }

    public function handleFailureEscalation(TransitionInput $input): void
    {
        $this->log('static:failure_escalation', $input);
    }

    public function cleanupFailure(TransitionInput $input): void
    {
        $this->log('static:failure_cleanup', $input);
    }
}
