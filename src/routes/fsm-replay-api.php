<?php

declare(strict_types=1);

use Fsm\Http\Controllers\FsmReplayApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FSM Replay API Routes
|--------------------------------------------------------------------------
|
| These routes provide API endpoints for FSM replay functionality, enabling
| deterministic state restoration, auditing, debugging, and analytics.
|
| To use these routes, include them in your application by publishing this
| file and then including it in your RouteServiceProvider or routes/api.php.
|
*/

Route::prefix('api/fsm/replay')
    ->middleware(['api'])
    ->group(function () {

        // Get transition history for a specific FSM instance
        Route::post('/history', [FsmReplayApiController::class, 'getHistory'])
            ->name('fsm.replay.history');

        // Replay transitions to reconstruct state deterministically
        Route::post('/transitions', [FsmReplayApiController::class, 'replayTransitions'])
            ->name('fsm.replay.transitions');

        // Validate transition history for consistency
        Route::post('/validate', [FsmReplayApiController::class, 'validateHistory'])
            ->name('fsm.replay.validate');

        // Get transition statistics and analytics
        Route::post('/statistics', [FsmReplayApiController::class, 'getStatistics'])
            ->name('fsm.replay.statistics');
    });
