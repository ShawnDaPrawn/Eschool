// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionDetail _$TutorSessionDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorSessionDetail',
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
            'page',
            'last_page',
            'turns',
            'pending_photos',
          ],
        );
        final val = TutorSessionDetail(
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
              _$TutorSessionDetailStateEnumEnumMap,
              v,
              unknownValue: TutorSessionDetailStateEnum.unknownDefaultOpenApi,
            ),
          ),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
          turns: $checkedConvert(
            'turns',
            (v) => (v as List<dynamic>)
                .map((e) => TutorTurn.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          pendingPhotos: $checkedConvert(
            'pending_photos',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => TutorPhotoReceipt.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
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
        'lastPage': 'last_page',
        'pendingPhotos': 'pending_photos',
      },
    );

Map<String, dynamic> _$TutorSessionDetailToJson(TutorSessionDetail instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'id': instance.id,
      'learner_id': instance.learnerId,
      'subject_id': instance.subjectId,
      'group_id': instance.groupId,
      'session_year_id': instance.sessionYearId,
      'state': _$TutorSessionDetailStateEnumEnumMap[instance.state]!,
      'page': instance.page,
      'last_page': instance.lastPage,
      'turns': instance.turns.map((e) => e.toJson()).toList(),
      'pending_photos': instance.pendingPhotos.map((e) => e.toJson()).toList(),
    };

const _$TutorSessionDetailStateEnumEnumMap = {
  TutorSessionDetailStateEnum.open: 'open',
  TutorSessionDetailStateEnum.closed: 'closed',
  TutorSessionDetailStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
