// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_moderation_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicModerationWrite _$AcademicModerationWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicModerationWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['reason', 'rows']);
  final val = AcademicModerationWrite(
    reason: $checkedConvert('reason', (v) => v as String),
    rows: $checkedConvert(
      'rows',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicModerationRow.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicModerationWriteToJson(
  AcademicModerationWrite instance,
) => <String, dynamic>{
  'reason': instance.reason,
  'rows': instance.rows.map((e) => e.toJson()).toList(),
};
