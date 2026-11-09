// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_paper_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestPaperOption _$PracticeTestPaperOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestPaperOption',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['version_id', 'title', 'paper_year', 'paper_code'],
    );
    final val = PracticeTestPaperOption(
      versionId: $checkedConvert('version_id', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      paperYear: $checkedConvert('paper_year', (v) => (v as num).toInt()),
      paperCode: $checkedConvert('paper_code', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'versionId': 'version_id',
    'paperYear': 'paper_year',
    'paperCode': 'paper_code',
  },
);

Map<String, dynamic> _$PracticeTestPaperOptionToJson(
  PracticeTestPaperOption instance,
) => <String, dynamic>{
  'version_id': instance.versionId,
  'title': instance.title,
  'paper_year': instance.paperYear,
  'paper_code': instance.paperCode,
};
