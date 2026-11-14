// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryTopic _$SharedLibraryTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SharedLibraryTopic', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'name',
          'framework',
          'subject_code',
          'grade',
        ],
      );
      final val = SharedLibraryTopic(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        framework: $checkedConvert('framework', (v) => v as String),
        subjectCode: $checkedConvert('subject_code', (v) => v as String),
        grade: $checkedConvert('grade', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'subjectCode': 'subject_code'});

Map<String, dynamic> _$SharedLibraryTopicToJson(SharedLibraryTopic instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'framework': instance.framework,
      'subject_code': instance.subjectCode,
      'grade': instance.grade,
    };
