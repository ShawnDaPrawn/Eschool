// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_optional_framework.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineOptionalFramework _$DisciplineOptionalFrameworkFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineOptionalFramework', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'title', 'revision', 'definition'],
  );
  final val = DisciplineOptionalFramework(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num?)?.toInt(),
    ),
    title: $checkedConvert('title', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    definition: $checkedConvert(
      'definition',
      (v) => DisciplineDefinition.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$DisciplineOptionalFrameworkToJson(
  DisciplineOptionalFramework instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year_id': ?instance.sessionYearId,
  'title': instance.title,
  'revision': instance.revision,
  'definition': instance.definition.toJson(),
};
