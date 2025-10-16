<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Enums;

use Fsm\Contracts\FsmStateEnum;

/**
 * Another enum type for testing generic FSM support.
 * This ensures the package works with any enum implementing FsmStateEnum.
 */
enum WorkflowState: string implements FsmStateEnum
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public function displayName(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => '📝',
            self::UnderReview => '👀',
            self::Approved => '✅',
            self::Published => '🚀',
            self::Archived => '📦',
        };
    }
}
