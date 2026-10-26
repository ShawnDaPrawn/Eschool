// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThreadResult _$SchoolAssistanceThreadResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceThreadResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'revision', 'state', 'school_id', 'reader_id'],
    );
    final val = SchoolAssistanceThreadResult(
      id: $checkedConvert('id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SchoolAssistanceThreadResultStateEnumEnumMap,
          v,
          unknownValue:
              SchoolAssistanceThreadResultStateEnum.unknownDefaultOpenApi,
        ),
      ),
      messageId: $checkedConvert('message_id', (v) => v as String?),
      assignedUserId: $checkedConvert(
        'assigned_user_id',
        (v) => (v as num?)?.toInt(),
      ),
      reason: $checkedConvert('reason', (v) => v as String?),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'messageId': 'message_id',
    'assignedUserId': 'assigned_user_id',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceThreadResultToJson(
  SchoolAssistanceThreadResult instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'state': _$SchoolAssistanceThreadResultStateEnumEnumMap[instance.state]!,
  'message_id': ?instance.messageId,
  'assigned_user_id': ?instance.assignedUserId,
  'reason': ?instance.reason,
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};

const _$SchoolAssistanceThreadResultStateEnumEnumMap = {
  SchoolAssistanceThreadResultStateEnum.open: 'open',
  SchoolAssistanceThreadResultStateEnum.closed: 'closed',
  SchoolAssistanceThreadResultStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
