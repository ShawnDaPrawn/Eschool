// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_basis.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordBasis _$DisciplineRecordBasisFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineRecordBasis',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'definition',
        'infraction_name',
        'window_start',
        'occurrence_count',
      ],
    );
    final val = DisciplineRecordBasis(
      definition: $checkedConvert(
        'definition',
        (v) => DisciplineDefinition.fromJson(v as Map<String, dynamic>),
      ),
      infractionName: $checkedConvert('infraction_name', (v) => v as String),
      windowStart: $checkedConvert('window_start', (v) => v as String),
      occurrenceCount: $checkedConvert(
        'occurrence_count',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'infractionName': 'infraction_name',
    'windowStart': 'window_start',
    'occurrenceCount': 'occurrence_count',
  },
);

Map<String, dynamic> _$DisciplineRecordBasisToJson(
  DisciplineRecordBasis instance,
) => <String, dynamic>{
  'definition': instance.definition.toJson(),
  'infraction_name': instance.infractionName,
  'window_start': instance.windowStart,
  'occurrence_count': instance.occurrenceCount,
};
