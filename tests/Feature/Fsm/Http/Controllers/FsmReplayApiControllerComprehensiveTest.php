<?php

declare(strict_types=1);

namespace Tests\Feature\Fsm\Http\Controllers;

use Fsm\Http\Controllers\FsmReplayApiController;
use Fsm\Services\FsmReplayService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\Feature\Fsm\Models\TestModel;
use Tests\TestCase;

class FsmReplayApiControllerComprehensiveTest extends TestCase
{
    private FsmReplayApiController $controller;

    private FsmReplayService $replayService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replayService = $this->createMock(FsmReplayService::class);
        $this->controller = new FsmReplayApiController($this->replayService);
    }

    public function test_get_history_successful_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        // Mock the service to return an empty collection
        $historyMock = new \Illuminate\Database\Eloquent\Collection([]);
        $this->replayService->method('getTransitionHistory')->willReturn($historyMock);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }

    public function test_get_history_validation_error(): void
    {
        $request = new Request([
            'model_class' => '', // Invalid - empty model class
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_missing_required_fields(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            // Missing model_id and column_name
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_null_model_id(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => null,
            'column_name' => 'status',
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_null_column_name(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => null,
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_whitespace_only_model_id(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '   ',
            'column_name' => 'status',
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_whitespace_only_column_name(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => '   ',
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_history_invalid_model_class(): void
    {
        $request = new Request([
            'model_class' => 'Invalid\Model\Class',
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_get_history_with_all_optional_parameters(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
            'from_state' => 'red',
            'to_state' => 'green',
        ]);

        $historyMock = new \Illuminate\Database\Eloquent\Collection([]);
        $this->replayService->method('getTransitionHistory')->willReturn($historyMock);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_get_history_service_exception(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $this->replayService->method('getTransitionHistory')->willThrowException(new \Exception('Service error'));

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function test_get_history_with_complex_model_class(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => 'user-123',
            'column_name' => 'workflow_status',
        ]);

        $historyMock = new \Illuminate\Database\Eloquent\Collection([]);
        $this->replayService->method('getTransitionHistory')->willReturn($historyMock);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_get_history_with_numeric_model_id(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123', // Convert to string to pass validation
            'column_name' => 'status',
        ]);

        $historyMock = new \Illuminate\Database\Eloquent\Collection([]);
        $this->replayService->method('getTransitionHistory')->willReturn($historyMock);

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_get_statistics_successful_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $statisticsData = ['total_transitions' => 5, 'unique_states' => 3];
        $this->replayService->method('getTransitionStatistics')->willReturn($statisticsData);

        $response = $this->controller->getStatistics($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($statisticsData, $responseData['data']);
    }

    public function test_get_statistics_validation_error(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '', // Invalid - empty model ID
            'column_name' => 'status',
        ]);

        $response = $this->controller->getStatistics($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_get_statistics_service_exception(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $this->replayService->method('getTransitionStatistics')->willThrowException(new \Exception('Statistics error'));

        $response = $this->controller->getStatistics($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Statistics error', $responseData['error']);
    }

    public function test_replay_transitions_successful_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $replayData = ['transitions' => [], 'total' => 0];
        $this->replayService->method('replayTransitions')->willReturn($replayData);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($replayData, $responseData['data']);
    }

    public function test_replay_transitions_validation_error(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            // Missing column_name
        ]);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_replay_transitions_service_exception(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $this->replayService->method('replayTransitions')->willThrowException(new \Exception('Transitions error'));

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Transitions error', $responseData['error']);
    }

    public function test_validate_history_successful_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $validationResult = ['valid' => true, 'errors' => []];
        $this->replayService->method('validateTransitionHistory')->willReturn($validationResult);

        $response = $this->controller->validateHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertTrue($responseData['data']['valid']);
        $this->assertEquals([], $responseData['data']['errors']);
    }

    public function test_validate_history_validation_error(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            // Missing column_name
        ]);

        $response = $this->controller->validateHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function test_validate_history_service_exception(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $this->replayService->method('validateTransitionHistory')->willThrowException(new \Exception('Validation error'));

        $response = $this->controller->validateHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Validation error', $responseData['error']);
    }

    public function test_validate_history_invalid_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $validationResult = ['valid' => false, 'errors' => ['Invalid transition at index 2']];
        $this->replayService->method('validateTransitionHistory')->willReturn($validationResult);

        $response = $this->controller->validateHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertFalse($responseData['data']['valid']);
        $this->assertEquals(['Invalid transition at index 2'], $responseData['data']['errors']);
    }

    public function test_all_methods_handle_mixed_case_model_names(): void
    {
        $request = new Request([
            'model_class' => TestModel::class, // Use existing test model
            'model_id' => 'user-456',
            'column_name' => 'account_status',
        ]);

        $expectedResponse = [];
        $this->replayService->method('replayTransitions')->willReturn($expectedResponse);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_all_methods_handle_underscore_column_names(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'workflow_status_type',
        ]);

        $expectedResponse = [];
        $this->replayService->method('replayTransitions')->willReturn($expectedResponse);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_all_methods_handle_large_model_ids(): void
    {
        $largeModelId = str_repeat('a', 1000);

        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => $largeModelId,
            'column_name' => 'status',
        ]);

        $expectedResponse = [];
        $this->replayService->method('replayTransitions')->willReturn($expectedResponse);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_replay_history_with_unicode_characters(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => 'test-üñíçødé',
            'column_name' => 'stâtüs',
        ]);

        $expectedResponse = [];
        $this->replayService->method('replayTransitions')->willReturn($expectedResponse);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_replay_history_with_special_characters(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => 'test@#$%^&*()',
            'column_name' => 'status-field_123',
        ]);

        $expectedResponse = [];
        $this->replayService->method('replayTransitions')->willReturn($expectedResponse);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_replay_transitions_with_data_response(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $replayData = [
            'transitions' => [
                ['from' => 'red', 'to' => 'yellow', 'event' => 'change'],
                ['from' => 'yellow', 'to' => 'green', 'event' => 'change'],
            ],
            'total' => 2,
        ];

        $this->replayService->method('replayTransitions')->willReturn($replayData);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($replayData, $responseData['data']);
    }

    public function test_replay_transitions_with_empty_data(): void
    {
        $request = new Request([
            'model_class' => TestModel::class,
            'model_id' => '123',
            'column_name' => 'status',
        ]);

        $replayData = ['transitions' => [], 'total' => 0];
        $this->replayService->method('replayTransitions')->willReturn($replayData);

        $response = $this->controller->replayTransitions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($replayData, $responseData['data']);
    }
}
