# Documentation: RunCallbackJob.php

Original file: `src/Fsm/Jobs/RunCallbackJob.php`

# RunCallbackJob Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Properties](#class-properties)
- [Constructor](#constructor)
- [handle Method](#handle-method)

## Introduction
The `RunCallbackJob` class is a job that implements the `ShouldQueue` interface in a Laravel application. Its primary purpose is to asynchronously execute a callback method with specified parameters and input data. This class is a part of the State Machine (FSM) system, designed to handle state transitions and actions triggered by those transitions within the application. Queuing this job allows the application to perform tasks without blocking the main request cycle, enhancing performance and user experience.

## Class Properties
The `RunCallbackJob` class contains the following properties:

| Property    | Type                     | Description                                                    |
|-------------|--------------------------|----------------------------------------------------------------|
| `callable`  | `string`                 | The name of the callable (function or method) to execute.      |
| `parameters` | `array<string,mixed>`    | An array of parameters to pass to the callable.                |
| `inputData` | `array<string,mixed>`    | An array containing input data, including model information and additional context.|

## Constructor
```php
public function __construct(
    public string $callable,
    public array $parameters,
    public array $inputData,
)
```

### Purpose
The constructor initializes a new instance of the `RunCallbackJob` class with the callable, parameters, and input data.

### Parameters
- `callable` (string): The name of the callable to be invoked.
- `parameters` (array<string, mixed>): An associative array containing named arguments that will be passed to the callable.
- `inputData` (array<string, mixed>): An associative array containing essential input data, such as model class and ID.

---

## handle Method
```php
public function handle(): void
```

### Purpose
The `handle` method retrieves a model instance based on the `inputData`, processes the data, and invokes the specified callable with the appropriate parameters.

### Functionality
1. **Model Retrieval**: The method begins by determining the model class from the `inputData` and attempts to find the model instance using the provided model ID:
   ```php
   $modelClass = $this->inputData['model_class'];
   $model = $modelClass::find($this->inputData['model_id']);
   ```
   If the model is not found, it logs a warning and returns early, indicating that the callback cannot be executed.

2. **Data Preparation**: If the model is found, the method prepares the `$data` array for the callable. It adds the retrieved model to this data while removing sensitive information such as `model_class` and `model_id`:
   ```php
   $data['model'] = $model;
   unset($data['model_class'], $data['model_id']);
   ```

3. **Context Preservation**: The method keeps track of any original context from `inputData` to ensure that it can be checked later:
   ```php
   $originalContext = $this->inputData['context'] ?? null;
   ```

4. **Transition Input Creation**: It then creates an instance of `TransitionInput` using the modified `$data` array:
   ```php
   $input = TransitionInput::from($data);
   ```

5. **Deserialization Check**: After creating the `TransitionInput`, the method verifies if the original context was lost during deserialization. If so, another warning is logged:
   ```php
   if ($originalContext !== null && $input->context === null) {
       // Log warning
   }
   ```

6. **Callable Invocation**: Finally, the specified callable is invoked using the `App::call()` method, passing in an array that merges the provided parameters with the newly created `$input`:
   ```php
   App::call($this->callable, array_merge($this->parameters, ['input' => $input]));
   ```

### Return Values
The `handle` method does not return a value; it is a `void` function.

## Conclusion
The `RunCallbackJob` class is an essential part of the Laravel FSM system that enables asynchronous execution of callbacks. By organizing the code to interact with the appropriate models and handling input data seamlessly, this class affords developers a clean, efficient way to manage state transitions and related actions.