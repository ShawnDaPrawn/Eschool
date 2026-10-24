// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_mark_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMarkWrite _$AcademicMarkWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicMarkWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['rows']);
      final val = AcademicMarkWrite(
        rows: $checkedConvert(
          'rows',
          (v) => (v as List<dynamic>)
              .map((e) => AcademicMarkRow.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$AcademicMarkWriteToJson(AcademicMarkWrite instance) =>
    <String, dynamic>{'rows': instance.rows.map((e) => e.toJson()).toList()};
