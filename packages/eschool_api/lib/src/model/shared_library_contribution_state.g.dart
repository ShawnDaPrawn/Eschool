// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contribution_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributionState _$SharedLibraryContributionStateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryContributionState', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'state', 'practice_item_id']);
  final val = SharedLibraryContributionState(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$SharedLibraryContributionStateStateEnumEnumMap,
        v,
        unknownValue:
            SharedLibraryContributionStateStateEnum.unknownDefaultOpenApi,
      ),
    ),
    practiceItemId: $checkedConvert(
      'practice_item_id',
      (v) => (v as num).toInt(),
    ),
  );
  return val;
}, fieldKeyMap: const {'practiceItemId': 'practice_item_id'});

Map<String, dynamic> _$SharedLibraryContributionStateToJson(
  SharedLibraryContributionState instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': _$SharedLibraryContributionStateStateEnumEnumMap[instance.state]!,
  'practice_item_id': instance.practiceItemId,
};

const _$SharedLibraryContributionStateStateEnumEnumMap = {
  SharedLibraryContributionStateStateEnum.awaitingApproval: 'awaiting_approval',
  SharedLibraryContributionStateStateEnum.shared: 'shared',
  SharedLibraryContributionStateStateEnum.declined: 'declined',
  SharedLibraryContributionStateStateEnum.withdrawn: 'withdrawn',
  SharedLibraryContributionStateStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
