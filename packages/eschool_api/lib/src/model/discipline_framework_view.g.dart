// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_framework_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFrameworkView _$DisciplineFrameworkViewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineFrameworkView', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['session_year_id', 'framework']);
  final val = DisciplineFrameworkView(
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    framework: $checkedConvert(
      'framework',
      (v) => v == null
          ? null
          : DisciplineOptionalFramework.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$DisciplineFrameworkViewToJson(
  DisciplineFrameworkView instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'framework': instance.framework?.toJson(),
};
