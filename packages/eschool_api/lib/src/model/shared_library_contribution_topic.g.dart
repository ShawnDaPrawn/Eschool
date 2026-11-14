// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contribution_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributionTopic _$SharedLibraryContributionTopicFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryContributionTopic', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['name', 'framework', 'subject_code', 'grade'],
  );
  final val = SharedLibraryContributionTopic(
    name: $checkedConvert('name', (v) => v as String),
    framework: $checkedConvert('framework', (v) => v as String),
    subjectCode: $checkedConvert('subject_code', (v) => v as String),
    grade: $checkedConvert('grade', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'subjectCode': 'subject_code'});

Map<String, dynamic> _$SharedLibraryContributionTopicToJson(
  SharedLibraryContributionTopic instance,
) => <String, dynamic>{
  'name': instance.name,
  'framework': instance.framework,
  'subject_code': instance.subjectCode,
  'grade': instance.grade,
};
