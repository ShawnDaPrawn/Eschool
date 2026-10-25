// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressGroup _$ProgressGroupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProgressGroup',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'subject_id',
            'subject_name',
            'grade_name',
            'session_year_id',
          ],
        );
        final val = ProgressGroup(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          gradeName: $checkedConvert('grade_name', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          termIds: $checkedConvert(
            'term_ids',
            (v) =>
                (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'gradeName': 'grade_name',
        'sessionYearId': 'session_year_id',
        'termIds': 'term_ids',
      },
    );

Map<String, dynamic> _$ProgressGroupToJson(ProgressGroup instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'grade_name': instance.gradeName,
      'session_year_id': instance.sessionYearId,
      'term_ids': ?instance.termIds,
    };
