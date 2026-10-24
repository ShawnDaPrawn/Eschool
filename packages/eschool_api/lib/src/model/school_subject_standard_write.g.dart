// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_subject_standard_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolSubjectStandardWrite _$SchoolSubjectStandardWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolSubjectStandardWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['standard_id']);
  final val = SchoolSubjectStandardWrite(
    standardId: $checkedConvert('standard_id', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'standardId': 'standard_id'});

Map<String, dynamic> _$SchoolSubjectStandardWriteToJson(
  SchoolSubjectStandardWrite instance,
) => <String, dynamic>{'standard_id': instance.standardId};
