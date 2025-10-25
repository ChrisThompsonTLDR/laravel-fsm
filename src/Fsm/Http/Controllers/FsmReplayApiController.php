<?php

declare(strict_types=1);

namespace Fsm\Http\Controllers;

use Fsm\Data\ReplayHistoryRequest;
use Fsm\Data\ReplayHistoryResponse;
use Fsm\Data\ReplayStatisticsRequest;
use Fsm\Data\ReplayStatisticsResponse;
use Fsm\Data\ReplayTransitionsRequest;
use Fsm\Data\ReplayTransitionsResponse;
use Fsm\Data\ValidateHistoryRequest;
use Fsm\Data\ValidateHistoryResponse;
use Fsm\Services\FsmReplayService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * API Controller for FSM Replay functionality.
 *
 * Provides REST endpoints to access FSM event replay capabilities for
 * deterministic state restoration, auditing, debugging, and analytics.
 *
 * All endpoints require model class, model ID, and column name to identify
 * the specific FSM instance to replay.
 */
class FsmReplayApiController extends Controller
{
    public function __construct(
        private readonly FsmReplayService $replayService
    ) {}

    /**
     * Cast a validated model class string to class-string<Model> for type safety.
     *
     * @return class-string<Model>
     */
    private function toModelClassString(string $modelClass): string
    {
        // Since validation ensures this is a valid Model class, we can safely cast
        /** @var class-string<Model> */
        return $modelClass;
    }

    /**
     * Get transition history for a specific FSM instance.
     *
     * Returns the complete chronological list of state transitions for
     * the specified model instance and FSM column.
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $data = ReplayHistoryRequest::from($request);

            $history = $this->replayService->getTransitionHistory(
                $this->toModelClassString($data->modelClass),
                $data->modelId,
                $data->columnName
            );

            $response = ReplayHistoryResponse::from([
                'success' => true,
                'data' => [
                    'transitions' => $history->map(fn ($log) => $log->getReplayData())->toArray(),
                    'count' => $history->count(),
                ],
                'message' => 'Transition history retrieved successfully',
                'error' => null,
                'details' => null,
            ]);

            return response()->json($response->toArray());

        } catch (ValidationException $e) {
            $response = ReplayHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\InvalidArgumentException $e) {
            $response = ReplayHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => ['general' => [$e->getMessage()]],
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\Throwable $e) {
            $response = ReplayHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Failed to retrieve transition history',
                'error' => $e->getMessage(),
                'details' => null,
            ]);

            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Replay transitions to reconstruct state deterministically.
     *
     * Processes all transitions for the specified FSM instance in chronological
     * order to determine the final state and provide transition details.
     */
    public function replayTransitions(Request $request): JsonResponse
    {
        try {
            $data = ReplayTransitionsRequest::from($request);

            $replayResult = $this->replayService->replayTransitions(
                $this->toModelClassString($data->modelClass),
                $data->modelId,
                $data->columnName
            );

            $response = ReplayTransitionsResponse::from([
                'success' => true,
                'data' => $replayResult,
                'message' => 'Transitions replayed successfully',
                'error' => null,
                'details' => null,
            ]);

            return response()->json($response->toArray());

        } catch (ValidationException $e) {
            $response = ReplayTransitionsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\InvalidArgumentException $e) {
            $response = ReplayTransitionsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => ['general' => [$e->getMessage()]],
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\Throwable $e) {
            $response = ReplayTransitionsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Failed to replay transitions',
                'error' => $e->getMessage(),
                'details' => null,
            ]);

            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Validate transition history for consistency.
     *
     * Checks that the transition sequence is valid with no gaps or
     * inconsistencies that might indicate data corruption or concurrent
     * modification issues.
     */
    public function validateHistory(Request $request): JsonResponse
    {
        try {
            $data = ValidateHistoryRequest::from($request);

            $validation = $this->replayService->validateTransitionHistory(
                $this->toModelClassString($data->modelClass),
                $data->modelId,
                $data->columnName
            );

            $response = ValidateHistoryResponse::from([
                'success' => true,
                'data' => $validation,
                'message' => $validation['valid']
                    ? 'Transition history is valid'
                    : 'Transition history validation failed',
                'error' => null,
                'details' => null,
            ]);

            return response()->json($response->toArray());

        } catch (ValidationException $e) {
            $response = ValidateHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\InvalidArgumentException $e) {
            $response = ValidateHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => ['general' => [$e->getMessage()]],
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\Throwable $e) {
            $response = ValidateHistoryResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Failed to validate transition history',
                'error' => $e->getMessage(),
                'details' => null,
            ]);

            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Get transition statistics and analytics.
     *
     * Provides detailed analytics about FSM usage including state frequencies,
     * transition patterns, and usage metrics. Useful for performance analysis
     * and understanding user behavior patterns.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $data = ReplayStatisticsRequest::from($request);

            $statistics = $this->replayService->getTransitionStatistics(
                $this->toModelClassString($data->modelClass),
                $data->modelId,
                $data->columnName
            );

            $response = ReplayStatisticsResponse::from([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistics retrieved successfully',
                'error' => null,
                'details' => null,
            ]);

            return response()->json($response->toArray());

        } catch (ValidationException $e) {
            $response = ReplayStatisticsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\InvalidArgumentException $e) {
            $response = ReplayStatisticsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Validation failed',
                'error' => 'Validation failed',
                'details' => ['general' => [$e->getMessage()]],
            ]);

            return response()->json($response->toArray(), 422);
        } catch (\Throwable $e) {
            $response = ReplayStatisticsResponse::from([
                'success' => false,
                'data' => [],
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
                'details' => null,
            ]);

            return response()->json($response->toArray(), 500);
        }
    }
}
