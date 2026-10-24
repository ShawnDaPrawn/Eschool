// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_publish_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPublishWrite _$CurriculumPublishWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumPublishWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['revision']);
  final val = CurriculumPublishWrite(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CurriculumPublishWriteToJson(
  CurriculumPublishWrite instance,
) => <String, dynamic>{'revision': instance.revision};
