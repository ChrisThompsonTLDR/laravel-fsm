# Documentation Index

This index is automatically generated and lists all documentation files:

* **src/**
  * **Fsm/**
    * **Commands/**
      * [Documentation: FsmCacheCommand.php](src/Fsm/Commands/FsmCacheCommand.md)
      * [Documentation: FsmDiagramCommand.php](src/Fsm/Commands/FsmDiagramCommand.md)
      * [Documentation: MakeFsmCommand.php](src/Fsm/Commands/MakeFsmCommand.md)
      * [Documentation: PublishModularConfigCommand.php](src/Fsm/Commands/PublishModularConfigCommand.md)
      * [Documentation: TestReplayApiCommand.php](src/Fsm/Commands/TestReplayApiCommand.md)
    * **Contracts/**
      * [Documentation: FsmDefinition.php](src/Fsm/Contracts/FsmDefinition.md)
      * [Documentation: FsmExtension.php](src/Fsm/Contracts/FsmExtension.md)
      * [Documentation: FsmStateEnum.php](src/Fsm/Contracts/FsmStateEnum.md)
      * [Documentation: ModularStateDefinition.php](src/Fsm/Contracts/ModularStateDefinition.md)
      * [Documentation: ModularTransitionDefinition.php](src/Fsm/Contracts/ModularTransitionDefinition.md)
    * **Data/**
      * [Documentation: Dto.php](src/Fsm/Data/Dto.md)
      * [Documentation: FsmRuntimeDefinition.php](src/Fsm/Data/FsmRuntimeDefinition.md)
      * [Documentation: HierarchicalStateDefinition.php](src/Fsm/Data/HierarchicalStateDefinition.md)
      * [Documentation: ReplayHistoryRequest.php](src/Fsm/Data/ReplayHistoryRequest.md)
      * [Documentation: ReplayHistoryResponse.php](src/Fsm/Data/ReplayHistoryResponse.md)
      * [Documentation: ReplayStatisticsRequest.php](src/Fsm/Data/ReplayStatisticsRequest.md)
      * [Documentation: ReplayStatisticsResponse.php](src/Fsm/Data/ReplayStatisticsResponse.md)
      * [Documentation: ReplayTransitionsRequest.php](src/Fsm/Data/ReplayTransitionsRequest.md)
      * [Documentation: ReplayTransitionsResponse.php](src/Fsm/Data/ReplayTransitionsResponse.md)
      * [Documentation: StateDefinition.php](src/Fsm/Data/StateDefinition.md)
      * [Documentation: StateTimeAnalysisData.php](src/Fsm/Data/StateTimeAnalysisData.md)
      * [Documentation: StateTimelineEntryData.php](src/Fsm/Data/StateTimelineEntryData.md)
      * [Documentation: TransitionAction.php](src/Fsm/Data/TransitionAction.md)
      * [Documentation: TransitionCallback.php](src/Fsm/Data/TransitionCallback.md)
      * [Documentation: TransitionDefinition.php](src/Fsm/Data/TransitionDefinition.md)
      * [Documentation: TransitionGuard.php](src/Fsm/Data/TransitionGuard.md)
      * [Documentation: TransitionInput.php](src/Fsm/Data/TransitionInput.md)
      * [Documentation: ValidateHistoryRequest.php](src/Fsm/Data/ValidateHistoryRequest.md)
      * [Documentation: ValidateHistoryResponse.php](src/Fsm/Data/ValidateHistoryResponse.md)
    * **Events/**
      * [Documentation: StateTransitioned.php](src/Fsm/Events/StateTransitioned.md)
      * [Documentation: TransitionAttempted.php](src/Fsm/Events/TransitionAttempted.md)
      * [Documentation: TransitionFailed.php](src/Fsm/Events/TransitionFailed.md)
      * [Documentation: TransitionMetric.php](src/Fsm/Events/TransitionMetric.md)
      * [Documentation: TransitionSucceeded.php](src/Fsm/Events/TransitionSucceeded.md)
    * **Exceptions/**
      * [Documentation: FsmTransitionFailedException.php](src/Fsm/Exceptions/FsmTransitionFailedException.md)
    * **Guards/**
      * [Documentation: CompositeGuard.php](src/Fsm/Guards/CompositeGuard.md)
      * [Documentation: PolicyGuard.php](src/Fsm/Guards/PolicyGuard.md)
    * **Http/**
      * **Controllers/**
        * [Documentation: FsmReplayApiController.php](src/Fsm/Http/Controllers/FsmReplayApiController.md)
    * **Jobs/**
      * [Documentation: RunActionJob.php](src/Fsm/Jobs/RunActionJob.md)
      * [Documentation: RunCallbackJob.php](src/Fsm/Jobs/RunCallbackJob.md)
    * **Listeners/**
      * [Documentation: PersistStateTransitionedEvent.php](src/Fsm/Listeners/PersistStateTransitionedEvent.md)
    * **Models/**
      * [Documentation: FsmEventLog.php](src/Fsm/Models/FsmEventLog.md)
      * [Documentation: FsmLog.php](src/Fsm/Models/FsmLog.md)
    * **Services/**
      * [Documentation: BootstrapDetector.php](src/Fsm/Services/BootstrapDetector.md)
      * [Documentation: FsmEngineService.php](src/Fsm/Services/FsmEngineService.md)
      * [Documentation: FsmHistoryService.php](src/Fsm/Services/FsmHistoryService.md)
      * [Documentation: FsmLogger.php](src/Fsm/Services/FsmLogger.md)
      * [Documentation: FsmMetricsService.php](src/Fsm/Services/FsmMetricsService.md)
      * [Documentation: FsmReplayService.php](src/Fsm/Services/FsmReplayService.md)
    * **Support/**
      * [Documentation: DefinitionDiscoverer.php](src/Fsm/Support/DefinitionDiscoverer.md)
    * **Traits/**
      * [Documentation: HasFsm.php](src/Fsm/Traits/HasFsm.md)
      * [Documentation: StateNameStringConversion.php](src/Fsm/Traits/StateNameStringConversion.md)
    * **Verbs/**
      * [Documentation: FsmTransitioned.php](src/Fsm/Verbs/FsmTransitioned.md)
    * [Documentation: Constants.php](src/Fsm/Constants.md)
    * [Documentation: FsmBuilder.php](src/Fsm/FsmBuilder.md)
    * [Documentation: FsmExtensionRegistry.php](src/Fsm/FsmExtensionRegistry.md)
    * [Documentation: FsmRegistry.php](src/Fsm/FsmRegistry.md)
    * [Documentation: FsmServiceProvider.php](src/Fsm/FsmServiceProvider.md)
    * [Documentation: TransitionBuilder.php](src/Fsm/TransitionBuilder.md)
  * **database/**
    * **migrations/**
      * [Documentation: 2024_01_01_000000_create_fsm_logs_table.php](src/database/migrations/2024_01_01_000000_create_fsm_logs_table.md)
      * [Documentation: 2024_06_01_000000_add_duration_ms_to_fsm_logs_table.php](src/database/migrations/2024_06_01_000000_add_duration_ms_to_fsm_logs_table.md)
      * [Documentation: 2024_12_01_000000_create_fsm_event_logs_table.php](src/database/migrations/2024_12_01_000000_create_fsm_event_logs_table.md)
  * **routes/**
    * [Documentation: fsm-replay-api.php](src/routes/fsm-replay-api.md)
