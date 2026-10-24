// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentGroup _$ContentGroupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentGroup',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'subject_id',
            'subject_name',
            'grade_level_id',
            'grade_name',
          ],
        );
        final val = ContentGroup(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          gradeName: $checkedConvert('grade_name', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'gradeLevelId': 'grade_level_id',
        'gradeName': 'grade_name',
      },
    );

Map<String, dynamic> _$ContentGroupToJson(ContentGroup instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'grade_level_id': instance.gradeLevelId,
      'grade_name': instance.gradeName,
    };
