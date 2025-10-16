# Documentation: FsmReplayApiController.php

Original file: `src/Fsm/Http/Controllers/FsmReplayApiController.php`

# FsmReplayApiController Documentation

## Table of Contents
- [Introduction](#introduction)
- [Methods](#methods)
  - [getHistory](#gethistory)
  - [replayTransitions](#replaytransitions)
  - [validateHistory](#validatehistory)
  - [getStatistics](#getstatistics)
- [Routes](#routes)
- [Dependencies](#dependencies)

## Introduction
The `FsmReplayApiController` is a Laravel API controller designed to expose REST endpoints for accessing Finite State Machine (FSM) event replay functionalities. It includes methods for retrieving transition history, replaying transitions to reconstruct FSM states, validating transition sequences, and gathering statistics regarding FSM usage. This controller allows for deterministic state restoration, auditing, debugging, and analytics, making it an essential component in systems that rely on FSMs.

## Methods

### getHistory
```php
public function getHistory(Request $request): JsonResponse
```
#### Purpose
Retrieves the complete chronological list of state transitions for a specified FSM instance.

#### Parameters
- **Request $request**: The HTTP request containing the required parameters to identify the FSM instance.

#### Return Value
- **JsonResponse**: Returns a JSON response containing either the transition history or an error message.

#### Functionality
- The method extracts parameters from the request, specifically the model class, model ID, and column name.
- It invokes the `getTransitionHistory` method of the `FsmReplayService`.
- On success, the transitions are mapped to a response format that includes the transitions and a count of the total transitions.
- If validation fails or an exception occurs, the method returns an appropriate error response.

### replayTransitions
```php
public function replayTransitions(Request $request): JsonResponse
```
#### Purpose
Processes all transitions for a specified FSM instance to determine its final state and provide detailed transition information.

#### Parameters
- **Request $request**: The HTTP request containing the required parameters to identify the FSM instance.

#### Return Value
- **JsonResponse**: Returns a JSON response containing either the result of the replay or an error message.

#### Functionality
- Extracts parameters from the request to identify the FSM instance.
- Calls `replayTransitions` method of `FsmReplayService`.
- Returns a successful JSON response with transition results or an error message indicating what went wrong in case of failure.

### validateHistory
```php
public function validateHistory(Request $request): JsonResponse
```
#### Purpose
Validates the history of state transitions for consistency, ensuring no gaps or inconsistencies exist.

#### Parameters
- **Request $request**: The HTTP request containing the required parameters to identify the FSM instance.

#### Return Value
- **JsonResponse**: Returns a JSON response indicating whether the transition history is valid or has errors.

#### Functionality
- Receives user parameters, and invokes the `validateTransitionHistory` method in the service.
- Constructs a JSON response based on the validation results, returning either success or failure messages with relevant details when necessary.

### getStatistics
```php
public function getStatistics(Request $request): JsonResponse
```
#### Purpose
Provides detailed analytics about FSM usage, including transition patterns and state frequencies.

#### Parameters
- **Request $request**: The HTTP request carrying the parameters needed to acquire statistics.

#### Return Value
- **JsonResponse**: Returns a JSON response containing either the transition statistics or an error message.

#### Functionality
- Similar to other methods, it retrieves model class information and passes it to the service to get statistics.
- Constructs a JSON response to convey the success or failure of the statistics retrieval alongside the data when successful.

## Routes
The following routes are handled by the `FsmReplayApiController`:
- `GET /api/fsm/history`: Calls `getHistory` to retrieve transition history.
- `POST /api/fsm/replay`: Calls `replayTransitions` to replay transitions.
- `POST /api/fsm/validate`: Calls `validateHistory` to validate transition history.
- `GET /api/fsm/statistics`: Calls `getStatistics` to retrieve FSM usage statistics.

## Dependencies
The `FsmReplayApiController` relies on the following components:
- **FsmReplayService**: A service that handles all the underlying logic related to FSM replay functionalities.
- **Data Transfer Objects (DTO)**: Includes `ReplayHistoryRequest`, `ReplayHistoryResponse`, `ReplayTransitionsRequest`, `ReplayTransitionsResponse`, `ValidateHistoryRequest`, `ValidateHistoryResponse`, and `ReplayStatisticsRequest`, `ReplayStatisticsResponse`. These DTOs are responsible for encapsulating data used in requests and responses, providing structured and type-safe data handling.

This documentation provides a clear understanding of the `FsmReplayApiController`, its various functionalities, and how it fits within the broader application architecture. Developers can leverage this information to effectively interact with and extend the API's capabilities.