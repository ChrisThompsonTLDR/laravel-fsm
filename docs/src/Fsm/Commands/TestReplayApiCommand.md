# Documentation: TestReplayApiCommand.php

Original file: `src/Fsm/Commands/TestReplayApiCommand.php`

# TestReplayApiCommand Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Method Documentation](#method-documentation)
  - [handle](#handle)
  - [createTestData](#createtestdata)
  - [testHistoryEndpoint](#testhistoryendpoint)
  - [testReplayEndpoint](#testreplayendpoint)
  - [testValidateEndpoint](#testvalidateendpoint)
  - [testStatisticsEndpoint](#teststatisticsendpoint)

## Introduction
The `TestReplayApiCommand` class is a console command within the Laravel application that facilitates testing the functionality of the FSM (Finite State Machine) Replay API. Its primary purpose is to verify that the Replay API operates as expected by creating test data and engaging with various API endpoints, such as getting history, replaying transitions, validating history, and retrieving statistics.

## Class Overview
The `TestReplayApiCommand` extends the `Illuminate\Console\Command`, allowing it to be executed from the command line. It provides several options for specifying which model to test, the model ID, the column name for status, and an option to create test data.

### Properties
| Property            | Description                                                                      |
|---------------------|----------------------------------------------------------------------------------|
| `$signature`        | The name and signature of the console command, including available options.     |
| `$description`      | A short description of what this command does.                                  |

## Method Documentation

### handle
```php
public function handle(): int
```
**Purpose**: Executes the command logic for testing the FSM Replay API.

**Parameters**: None

**Return Values**: 
- Returns an integer status code:
  - `0` on success.
  - `1` on failure with an error message.

**Functionality**: 
- Initializes logging output to the console.
- Retrieves and validates input options such as `model-class`, `model-id`, and `column-name`.
- Creates test data if the `create-test-data` option is set.
- Calls API testing methods for the history, replay, validation, and statistics endpoints.
- Outputs completion status to the console.

### createTestData
```php
private function createTestData(string $modelClass, string $modelId, string $columnName): void
```
**Purpose**: Generates test transition data for a specified model.

**Parameters**:
- `string $modelClass`: The fully qualified class name of the model to be tested.
- `string $modelId`: The ID of the model instance for which to create test data.
- `string $columnName`: The name of the status column to be used.

**Return Values**: None

**Functionality**: 
- Logs a message indicating test transition data creation.
- Defines a set of predefined transitions simulating state changes.
- Iterates through each transition, utilizing `FsmEventLog::updateOrCreate` to save test records.
- Indicates successful creation of test data through logging.

### testHistoryEndpoint
```php
private function testHistoryEndpoint(FsmReplayApiController $controller, string $modelClass, string $modelId, string $columnName): void
```
**Purpose**: Tests the history endpoint of the FSM Replay API.

**Parameters**:
- `FsmReplayApiController $controller`: An instance of the replay controller to invoke the endpoint.
- `string $modelClass`: The fully qualified class name of the model.
- `string $modelId`: The ID of the model to fetch history for.
- `string $columnName`: The column name regarding which the history is retrieved.

**Return Values**: None

**Functionality**: 
- Logs information about the test being conducted.
- Constructs a request for the `getHistory` method of the controller.
- Parses the response and checks for success, logging errors where applicable.
- Prints the number of transitions retrieved and the details of the first transition, if available.

### testReplayEndpoint
```php
private function testReplayEndpoint(FsmReplayApiController $controller, string $modelClass, string $modelId, string $columnName): void
```
**Purpose**: Tests the replay endpoint of the FSM Replay API.

**Parameters**:
- `FsmReplayApiController $controller`: The controller instance for invoking the replay API.
- `string $modelClass`: The class name of the model for which to replay transitions.
- `string $modelId`: The ID of the model for the replay.
- `string $columnName`: The status column used in the replay.

**Return Values**: None

**Functionality**: 
- Logs the beginning of the test.
- Prepares a request to call the `replayTransitions` method on the controller.
- Validates the response, checking the success indicator and logging the results, including initial and final states.

### testValidateEndpoint
```php
private function testValidateEndpoint(FsmReplayApiController $controller, string $modelClass, string $modelId, string $columnName): void
```
**Purpose**: Tests the validation endpoint of the FSM Replay API.

**Parameters**:
- `FsmReplayApiController $controller`: Instance of the replay controller.
- `string $modelClass`: The model class to validate.
- `string $modelId`: The ID of the model instance to validate.
- `string $columnName`: The column of the model to validate.

**Return Values**: None

**Functionality**: 
- Starts with a log message indicating validation.
- Constructs a request to the `validateHistory` method.
- Assesses the response for success and logs the validation results, detailing any errors found.

### testStatisticsEndpoint
```php
private function testStatisticsEndpoint(FsmReplayApiController $controller, string $modelClass, string $modelId, string $columnName): void
```
**Purpose**: Tests the statistics endpoint of the FSM Replay API.

**Parameters**:
- `FsmReplayApiController $controller`: The controller for statistics retrieval.
- `string $modelClass`: The class of the model to get statistics for.
- `string $modelId`: The specific instance of the model.
- `string $columnName`: The column name of interest for statistics.

**Return Values**: None

**Functionality**: 
- Logs the start of the test.
- Sends a request to `getStatistics` on the controller.
- Evaluates the returned data for success and logs various statistics including total transitions and unique states, detailing state frequencies if available.

## Conclusion
The `TestReplayApiCommand` class serves as an essential tool for developers working with the FSM Replay API in this Laravel application. Through its structured testing methodologies, it contributes significantly to ensuring the integrity and reliability of API interactions within the system. By utilizing this command, developers can validate functionality and accurate state management effectively.