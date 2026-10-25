// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionList _$TutorSessionListFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorSessionList',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'learner_id',
            'page',
            'last_page',
            'sessions',
          ],
        );
        final val = TutorSessionList(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
          sessions: $checkedConvert(
            'sessions',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      TutorSessionListItem.fromJson(e as Map<String, dynamic>),
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
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$TutorSessionListToJson(TutorSessionList instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'page': instance.page,
      'last_page': instance.lastPage,
      'sessions': instance.sessions.map((e) => e.toJson()).toList(),
    };
