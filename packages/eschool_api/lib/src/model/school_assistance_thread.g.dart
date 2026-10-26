// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThread _$SchoolAssistanceThreadFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceThread',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'state',
        'learner_id',
        'expires_at',
        'page',
        'last_page',
        'messages',
        'school_id',
        'reader_id',
      ],
    );
    final val = SchoolAssistanceThread(
      id: $checkedConvert('id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SchoolAssistanceThreadStateEnumEnumMap,
          v,
          unknownValue: SchoolAssistanceThreadStateEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      messages: $checkedConvert(
        'messages',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  SchoolAssistanceMessage.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'expiresAt': 'expires_at',
    'lastPage': 'last_page',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceThreadToJson(
  SchoolAssistanceThread instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'state': _$SchoolAssistanceThreadStateEnumEnumMap[instance.state]!,
  'learner_id': instance.learnerId,
  'expires_at': instance.expiresAt.toIso8601String(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'messages': instance.messages.map((e) => e.toJson()).toList(),
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};

const _$SchoolAssistanceThreadStateEnumEnumMap = {
  SchoolAssistanceThreadStateEnum.open: 'open',
  SchoolAssistanceThreadStateEnum.closed: 'closed',
  SchoolAssistanceThreadStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
