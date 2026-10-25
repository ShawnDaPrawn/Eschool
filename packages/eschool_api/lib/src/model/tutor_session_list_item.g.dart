// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_list_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionListItem _$TutorSessionListItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorSessionListItem', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'subject_id', 'state']);
  final val = TutorSessionListItem(
    id: $checkedConvert('id', (v) => v as String),
    subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$TutorSessionListItemStateEnumEnumMap,
        v,
        unknownValue: TutorSessionListItemStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'subjectId': 'subject_id'});

Map<String, dynamic> _$TutorSessionListItemToJson(
  TutorSessionListItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'subject_id': instance.subjectId,
  'state': _$TutorSessionListItemStateEnumEnumMap[instance.state]!,
};

const _$TutorSessionListItemStateEnumEnumMap = {
  TutorSessionListItemStateEnum.open: 'open',
  TutorSessionListItemStateEnum.closed: 'closed',
  TutorSessionListItemStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
