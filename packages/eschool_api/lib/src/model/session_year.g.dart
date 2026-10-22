// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'session_year.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SessionYear _$SessionYearFromJson(Map<String, dynamic> json) => $checkedCreate(
  'SessionYear',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'name', 'start_date', 'end_date', 'terms'],
    );
    final val = SessionYear(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      startDate: $checkedConvert(
        'start_date',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      endDate: $checkedConvert(
        'end_date',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      terms: $checkedConvert(
        'terms',
        (v) => (v as List<dynamic>)
            .map((e) => Term.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'startDate': 'start_date', 'endDate': 'end_date'},
);

Map<String, dynamic> _$SessionYearToJson(SessionYear instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'start_date': instance.startDate?.toIso8601String(),
      'end_date': instance.endDate?.toIso8601String(),
      'terms': instance.terms.map((e) => e.toJson()).toList(),
    };
