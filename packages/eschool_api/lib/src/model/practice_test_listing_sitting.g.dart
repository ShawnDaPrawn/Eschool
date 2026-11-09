// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_listing_sitting.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestListingSitting _$PracticeTestListingSittingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestListingSitting', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'state', 'deadline_at']);
  final val = PracticeTestListingSitting(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$PracticeTestListingSittingStateEnumEnumMap,
        v,
        unknownValue: PracticeTestListingSittingStateEnum.unknownDefaultOpenApi,
      ),
    ),
    deadlineAt: $checkedConvert(
      'deadline_at',
      (v) => DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'deadlineAt': 'deadline_at'});

Map<String, dynamic> _$PracticeTestListingSittingToJson(
  PracticeTestListingSitting instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': _$PracticeTestListingSittingStateEnumEnumMap[instance.state]!,
  'deadline_at': instance.deadlineAt.toIso8601String(),
};

const _$PracticeTestListingSittingStateEnumEnumMap = {
  PracticeTestListingSittingStateEnum.open: 'open',
  PracticeTestListingSittingStateEnum.timeUp: 'time_up',
  PracticeTestListingSittingStateEnum.handedIn: 'handed_in',
  PracticeTestListingSittingStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
