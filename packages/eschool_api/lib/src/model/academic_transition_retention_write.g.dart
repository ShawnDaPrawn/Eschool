// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_retention_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionRetentionWrite _$AcademicTransitionRetentionWriteFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('AcademicTransitionRetentionWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['days', 'revision']);
      final val = AcademicTransitionRetentionWrite(
        days: $checkedConvert('days', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$AcademicTransitionRetentionWriteToJson(
  AcademicTransitionRetentionWrite instance,
) => <String, dynamic>{'days': instance.days, 'revision': instance.revision};
