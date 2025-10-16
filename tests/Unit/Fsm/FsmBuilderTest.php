<?php

declare(strict_types=1);

namespace Tests\Unit\Fsm;

use Fsm\FsmBuilder;
use Fsm\TransitionBuilder;
use PHPUnit\Framework\TestCase;

class FsmBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset the static definitions array before each test
        FsmBuilder::reset();
    }

    public function test_for_method_returns_transition_builder_instance(): void
    {
        $builder = FsmBuilder::for('App\\Models\\TestModel', 'status');
        $this->assertInstanceOf(TransitionBuilder::class, $builder);
    }

    public function test_register_fsm_stores_transition_details(): void
    {
        $modelClass = 'App\\Models\\Order';
        $columnName = 'order_status';
        $transitionDetails = [
            'from' => 'pending',
            'to' => 'processing',
            'guards' => [],
            'on_transition' => [],
        ];

        FsmBuilder::registerFsm($modelClass, $columnName, $transitionDetails);

        $retrievedFsm = FsmBuilder::getFsm($modelClass, $columnName);

        $this->assertIsArray($retrievedFsm);
        $this->assertCount(1, $retrievedFsm);
        $this->assertEquals($transitionDetails, $retrievedFsm[0]);
    }

    public function test_get_fsm_retrieves_correct_definitions(): void
    {
        $modelClass = 'App\\Models\\Invoice';
        $columnName = 'payment_status';
        $details1 = ['from' => 'draft', 'to' => 'sent'];
        $details2 = ['from' => 'sent', 'to' => 'paid'];

        FsmBuilder::registerFsm($modelClass, $columnName, $details1);
        FsmBuilder::registerFsm($modelClass, $columnName, $details2);

        $fsm = FsmBuilder::getFsm($modelClass, $columnName);

        $this->assertCount(2, $fsm);
        $this->assertEquals($details1, $fsm[0]);
        $this->assertEquals($details2, $fsm[1]);
    }

    public function test_get_fsm_returns_null_for_undefined_fsm(): void
    {
        $this->assertNull(FsmBuilder::getFsm('App\\Models\\NonExistent', 'status'));
    }

    public function test_fsms_are_unique_per_model_and_column(): void
    {
        $model1 = 'App\\Models\\Ticket';
        $column1 = 'ticket_state';
        $details1 = ['from' => 'open', 'to' => 'closed'];

        $model2 = 'App\\Models\\Ticket'; // Same model
        $column2 = 'priority_state';   // Different column
        $details2 = ['from' => 'low', 'to' => 'high'];

        $model3 = 'App\\Models\\User';    // Different model
        $column3 = 'user_status';      // Different column
        $details3 = ['from' => 'inactive', 'to' => 'active'];

        FsmBuilder::registerFsm($model1, $column1, $details1);
        FsmBuilder::registerFsm($model2, $column2, $details2);
        FsmBuilder::registerFsm($model3, $column3, $details3);

        $fsm1 = FsmBuilder::getFsm($model1, $column1);
        $this->assertCount(1, $fsm1);
        $this->assertEquals($details1, $fsm1[0]);

        $fsm2 = FsmBuilder::getFsm($model2, $column2);
        $this->assertCount(1, $fsm2);
        $this->assertEquals($details2, $fsm2[0]);

        $fsm3 = FsmBuilder::getFsm($model3, $column3);
        $this->assertCount(1, $fsm3);
        $this->assertEquals($details3, $fsm3[0]);
    }

    public function test_reset_clears_all_definitions(): void
    {
        FsmBuilder::registerFsm('App\\Models\\Post', 'post_status', ['from' => 'new', 'to' => 'published']);
        $this->assertNotNull(FsmBuilder::getFsm('App\\Models\\Post', 'post_status'));

        FsmBuilder::reset();
        $this->assertNull(FsmBuilder::getFsm('App\\Models\\Post', 'post_status'));
    }
}
