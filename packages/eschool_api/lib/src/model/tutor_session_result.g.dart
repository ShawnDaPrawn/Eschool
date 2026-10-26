// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionResult _$TutorSessionResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorSessionResult',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'id',
            'learner_id',
            'subject_id',
            'group_id',
            'session_year_id',
            'state',
          ],
        );
        final val = TutorSessionResult(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          id: $checkedConvert('id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$TutorSessionResultStateEnumEnumMap,
              v,
              unknownValue: TutorSessionResultStateEnum.unknownDefaultOpenApi,
            ),
          ),
          topicId: $checkedConvert('topic_id', (v) => (v as num?)?.toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'learnerId': 'learner_id',
        'subjectId': 'subject_id',
        'groupId': 'group_id',
        'sessionYearId': 'session_year_id',
        'topicId': 'topic_id',
      },
    );

Map<String, dynamic> _$TutorSessionResultToJson(TutorSessionResult instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'id': instance.id,
      'learner_id': instance.learnerId,
      'subject_id': instance.subjectId,
      'group_id': instance.groupId,
      'session_year_id': instance.sessionYearId,
      'state': _$TutorSessionResultStateEnumEnumMap[instance.state]!,
      'topic_id': ?instance.topicId,
    };

const _$TutorSessionResultStateEnumEnumMap = {
  TutorSessionResultStateEnum.open: 'open',
  TutorSessionResultStateEnum.closed: 'closed',
  TutorSessionResultStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
