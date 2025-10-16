# Documentation: 2024_01_01_000000_create_fsm_logs_table.php

Original file: `src/database/migrations/2024_01_01_000000_create_fsm_logs_table.php`

# Create FSM Logs Table Migration Documentation

## Table of Contents
- [Introduction](#introduction)
- [Class Overview](#class-overview)
- [Method Documentation](#method-documentation)
  - [up() Method](#up-method)
  - [down() Method](#down-method)

## Introduction

This file, `2024_01_01_000000_create_fsm_logs_table.php`, defines a Laravel migration for creating the `fsm_logs` table in the database. The purpose of this table is to store logs related to finite state machine (FSM) operations, including state transitions and associated context snapshots. This enables tracking and auditing of state changes for various models within the application, particularly when user-defined events trigger these transitions.

## Class Overview

```php
return new class extends Migration
```

This anonymous class extends the `Migration` class provided by Laravel, encapsulating the logic for migrating the `fsm_logs` table. It provides two major methods: `up()` and `down()`, which are crucial for creating and dropping the table, respectively.

## Method Documentation

### up() Method

```php
public function up(): void
```

#### Purpose
The `up()` method is responsible for creating the `fsm_logs` table. It defines the schema and structure of the table in the database.

#### Functionality
- **Creates a table named `fsm_logs`.**
- **Defines the following columns:**

| Column Name            | Type                | Description                                                   |
|-----------------------|---------------------|---------------------------------------------------------------|
| `id`                  | UUID                 | Primary key of the log entry.                                 |
| `subject`             | Nullable UUID Morph  | Links to the `verb_events.id` if a verb was involved.        |
| `model`               | UUID Morph           | References the associated model of the FSM log.              |
| `fsm_column`          | String               | Name of the FSM column being logged.                          |
| `from_state`         | String               | Previous state before the transition.                         |
| `to_state`           | String               | State after the transition.                                   |
| `transition_event`    | Nullable String      | User-defined name of the event that triggered the transition. |
| `context_snapshot`    | Nullable JSON        | Stores a snapshot of the context when the transition occurred. |
| `exception_details`   | Nullable Text        | Captures details of any exceptions that occurred during the transition. |
| `happened_at`        | Timestamp with timezone| Timestamp indicating when the transition occurred.          |

#### Notes
- The method uses the `Schema` facade to interact with the database schema.
- It employs the `Blueprint` class to define the structure of the table.
- The `useCurrent()` method sets the `happened_at` column to the current timestamp by default.

### down() Method

```php
public function down(): void
```

#### Purpose
The `down()` method defines the logic to undo the migration, specifically dropping the `fsm_logs` table.

#### Functionality
- **Calls the method to drop the `fsm_logs` table if it exists.**

#### Notes
- This ensures that the migration can be rolled back safely, maintaining the integrity of the application’s database state.

## Conclusion

This migration file is essential for implementing logging capabilities associated with the finite state machine functionality within the application. By creating the `fsm_logs` table, developers can efficiently track state transitions, understand the context of those transitions, and handle exceptions appropriately, contributing to enhanced debugging capabilities and overall application robustness.