# FSM Enum Conversion Test Coverage

## Overview

This document describes the comprehensive test suite for validating that the `fsm()->trigger()` helper and related methods work correctly with multiple enum types implementing `FsmStateEnum`.

## Bug Prevention

These tests were created to prevent regression of a critical bug where:
- `HasFsm::mapEvent()` hard-coded a check for `\Modules\Combat\Enums\UnitState`
- This caused fatal `Error: Object of class ... could not be converted to string` for any other enum type
- The fix replaced the hard-coded check with a generic `instanceof FsmStateEnum` check

## Test Files

### 1. FluentApiMultiEnumTest.php

**Purpose**: Validates that the fluent API works with multiple different enum types.

**Coverage**:
- `fsm()->trigger()` with `TestFeatureState` enum
- `fsm()->trigger()` with `TrafficLightState` enum
- `fsm()->can()` with multiple enum types
- `fsm()->dryRun()` with multiple enum types
- Enum value extraction without throwing errors
- `mapEvent()` respects current state for multiple enums
- Transitions with same event from different states

**Enum Types Tested**:
- `TestFeatureState` (app workflow states)
- `TrafficLightState` (traffic light control states)

### 2. EnumConversionRegressionTest.php

**Purpose**: Specific regression tests to ensure the hard-coded enum bug doesn't reoccur.

**Coverage**:
- Enum conversion for `TestFeatureState`
- Enum conversion for `TrafficLightState`
- Enum conversion for `WorkflowState`
- Validates `instanceof FsmStateEnum` is used (not concrete classes)
- Multiple enum types coexisting without conflicts
- `can()` method with multiple enum types
- `dryRun()` method with multiple enum types
- Critical test: No enum type throws "Object to string conversion" error

**Enum Types Tested**:
- `TestFeatureState` (app workflow states)
- `TrafficLightState` (traffic light control states)
- `WorkflowState` (document workflow states)

### 3. WorkflowState.php

**Purpose**: A third enum type for testing generic FSM support.

**Features**:
- Implements `FsmStateEnum` interface
- Uses `match` expression for `displayName()` and `icon()`
- Provides realistic workflow states: Draft, UnderReview, Approved, Published, Archived

## Running the Tests

```bash
# Run all FSM fluent API tests
vendor/bin/pest tests/Feature/Fsm/FluentApiMultiEnumTest.php
vendor/bin/pest tests/Feature/Fsm/EnumConversionRegressionTest.php

# Run specific test
vendor/bin/pest --filter=test_no_enum_throws_object_to_string_conversion_error

# Run with coverage
vendor/bin/pest --coverage
```

## Key Assertions

1. **No String Conversion Errors**: Enum types should never throw "Object could not be converted to string" errors
2. **Generic Support**: Any enum implementing `FsmStateEnum` should work
3. **No Hard-coded Types**: No domain-specific enum classes should be referenced in the package core
4. **Interface Checking**: Type checks should use `instanceof FsmStateEnum`, not concrete enum classes
5. **Multiple Enums**: Different enum types can coexist in the same application

## Future Considerations

- Add more enum types to increase coverage
- Test edge cases with custom enum methods
- Validate performance with large state machines
- Test integration with guards, actions, and callbacks using different enum types

