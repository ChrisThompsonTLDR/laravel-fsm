<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm\Exceptions;

use Fsm\Contracts\FsmStateEnum;
use Fsm\Exceptions\FsmTransitionFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// Mock FsmStateEnum for testing purposes
enum MockStateExceptionTest: string implements FsmStateEnum
{
    case From = 'from_state';
    case To = 'to_state';

    public function displayName(): string
    {
        return ucfirst($this->value);
    }

    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}

class FsmTransitionFailedExceptionTest extends TestCase
{
    public function test_constructor_and_getters_work_correctly(): void
    {
        $from = MockStateExceptionTest::From;
        $to = 'to_state_string';
        $reason = 'Test reason';
        $originalException = new RuntimeException('Original cause');

        $exception = new FsmTransitionFailedException(
            $from,
            $to,
            $reason,
            '',
            0,
            null,
            $originalException
        );

        $this->assertSame($from, $exception->getFromState());
        $this->assertSame($to, $exception->getToState());
        $this->assertEquals($reason, $exception->getReason());
        $this->assertSame($originalException, $exception->getOriginalException());
        $expectedMessage = "Transition from 'from_state' to 'to_state_string' failed: Test reason";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    public function test_for_invalid_transition_factory(): void
    {
        $from = MockStateExceptionTest::From;
        $to = MockStateExceptionTest::To;
        $modelClass = 'App\\Models\\Order';
        $fsmName = 'status';

        $exception = FsmTransitionFailedException::forInvalidTransition($from, $to, $modelClass, $fsmName);

        $this->assertSame($from, $exception->getFromState());
        $this->assertSame($to, $exception->getToState());
        $expectedReason = "No defined transition from 'from_state' to 'to_state' for App\\Models\\Order::status.";
        $this->assertEquals($expectedReason, $exception->getReason());
        $this->assertNull($exception->getOriginalException());
    }

    public function test_for_invalid_transition_with_string_states(): void
    {
        $from = 'string_from';
        $to = 'string_to';
        $modelClass = 'App\\Models\\Product';
        $fsmName = 'product_lifecycle';

        $exception = FsmTransitionFailedException::forInvalidTransition($from, $to, $modelClass, $fsmName);

        $this->assertSame($from, $exception->getFromState());
        $this->assertSame($to, $exception->getToState());
        $expectedReason = "No defined transition from 'string_from' to 'string_to' for App\\Models\\Product::product_lifecycle.";
        $this->assertEquals($expectedReason, $exception->getReason());
    }

    public function test_for_guard_failure_factory(): void
    {
        $from = 'pending';
        $to = MockStateExceptionTest::To;
        $modelClass = 'App\\Models\\Invoice';
        $fsmName = 'payment_status';
        $guardDescription = 'UserHasSufficientBalanceGuard';

        $exception = FsmTransitionFailedException::forGuardFailure(
            $from,
            $to,
            $guardDescription,
            $modelClass,
            $fsmName
        );

        $this->assertSame($from, $exception->getFromState());
        $this->assertSame($to, $exception->getToState());
        $expectedReason = "UserHasSufficientBalanceGuard failed for transition from 'pending' to 'to_state' on App\\Models\\Invoice::payment_status.";
        $this->assertEquals($expectedReason, $exception->getReason());
        $this->assertNull($exception->getOriginalException());
    }

    public function test_for_guard_failure_with_closure_description(): void
    {
        $exception = FsmTransitionFailedException::forGuardFailure('a', 'b', 'Closure', 'Model', 'col');
        $expectedReason = "Closure failed for transition from 'a' to 'b' on Model::col.";
        $this->assertEquals($expectedReason, $exception->getReason());
    }

    public function test_for_callback_exception_factory(): void
    {
        $from = MockStateExceptionTest::From;
        $to = 'completed';
        $modelClass = 'App\\Models\\Task';
        $fsmName = 'task_progress';
        $originalException = new RuntimeException('Something went wrong in callback');

        $exception = FsmTransitionFailedException::forCallbackException(
            $from,
            $to,
            'callback',
            $originalException,
            $modelClass,
            $fsmName
        );

        $this->assertSame($from, $exception->getFromState());
        $this->assertSame($to, $exception->getToState());
        $expectedReason = "Exception during 'callback' for transition from 'from_state' to 'completed' on App\\Models\\Task::task_progress: Something went wrong in callback";
        $this->assertEquals($expectedReason, $exception->getReason());
        $this->assertSame($originalException, $exception->getOriginalException());
    }
}
