# Documentation: fsm-replay-api.php

Original file: `src/routes/fsm-replay-api.php`

# fsm-replay-api.php Documentation

## Table of Contents
- [Introduction](#introduction)
- [Routes Overview](#routes-overview)
  - [POST /history](#post-history)
  - [POST /transitions](#post-transitions)
  - [POST /validate](#post-validate)
  - [POST /statistics](#post-statistics)

## Introduction
The `fsm-replay-api.php` file is responsible for defining the API routes related to the FSM (Finite State Machine) replay functionality in the application. It serves as a bridge between incoming API requests and the corresponding controller methods provided by the `FsmReplayApiController`. These routes facilitate deterministic state restoration, auditing, debugging, and analytics by allowing clients to access various FSM-related operations.

To utilize these routes, the file should be included in your application by either publishing it or incorporating it into your `RouteServiceProvider` or `routes/api.php`.

## Routes Overview

This file defines several key routes related to the FSM replay functionality. Each route is prefixed with `/api/fsm/replay` and is secured with the `api` middleware.

### POST /history
- **Route:** `/api/fsm/replay/history`
- **Controller Method:** `FsmReplayApiController@getHistory`
- **Route Name:** `fsm.replay.history`

#### Purpose
This route fetches the transition history for a specific FSM instance, allowing clients to analyze the path taken through the FSM.

#### Request Parameters
- `instance_id`: The unique identifier for the FSM instance whose history is requested.
  
#### Return Values
- Returns a JSON response containing the transition history, typically including:
  - A list of states the FSM has transitioned through.
  - Timestamps of each transition.
  
#### Functionality
The `getHistory` method retrieves transition data for a specified FSM instance. It queries the database for logged transitions, processes the results, and returns them in a structured format to the client.

### POST /transitions
- **Route:** `/api/fsm/replay/transitions`
- **Controller Method:** `FsmReplayApiController@replayTransitions`
- **Route Name:** `fsm.replay.transitions`

#### Purpose
This route allows clients to send transition data that will be replayed to reconstruct the FSM's state deterministically.

#### Request Parameters
- `instance_id`: The unique identifier for the FSM instance.
- `transitions`: An array of transition objects to replay.

#### Return Values
- Returns a JSON response indicating success or failure of the transition replay, along with the resulting state of the FSM.

#### Functionality
The `replayTransitions` method accepts transition data from the client, validates it, and then processes the transitions to update the FSM's state accordingly. If successful, it provides a detailed response including the new state.

### POST /validate
- **Route:** `/api/fsm/replay/validate`
- **Controller Method:** `FsmReplayApiController@validateHistory`
- **Route Name:** `fsm.replay.validate`

#### Purpose
This route validates transition history for consistency and correctness.

#### Request Parameters
- `instance_id`: The unique identifier for the FSM instance.
- `transitions`: An array of transition objects to be validated.

#### Return Values
- Returns a JSON response with validation results, stating whether the history is valid and any inconsistencies found.

#### Functionality
The `validateHistory` method checks the provided transition history against the FSM's defined rules and structure. If inconsistencies are identified, detailed feedback is provided to the client about what went wrong.

### POST /statistics
- **Route:** `/api/fsm/replay/statistics`
- **Controller Method:** `FsmReplayApiController@getStatistics`
- **Route Name:** `fsm.replay.statistics`

#### Purpose
This route retrieves transition statistics and analytical data regarding FSM usage.

#### Request Parameters
- `instance_id`: The unique identifier for the FSM instance.

#### Return Values
- Returns a JSON response containing:
  - Total transitions made.
  - Average time spent in each state.
  - Other relevant metrics.

#### Functionality
The `getStatistics` method aggregates data from the FSM instance's transition records and computes various statistics. This information is useful for performance monitoring and analysis of FSM behavior.

---

This documentation aims to provide a clear understanding of how to use and benefit from the FSM replay API routes defined in the `fsm-replay-api.php` file. By following the outlined specifications, developers can integrate and interact with the FSM replay capabilities in their applications effectively.