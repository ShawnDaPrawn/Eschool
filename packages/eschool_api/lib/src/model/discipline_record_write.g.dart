// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordWrite _$DisciplineRecordWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineRecordWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'occurred_on',
        'infraction_code',
        'description',
      ],
    );
    final val = DisciplineRecordWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      occurredOn: $checkedConvert('occurred_on', (v) => v as String),
      infractionCode: $checkedConvert('infraction_code', (v) => v as String),
      description: $checkedConvert('description', (v) => v as String),
      staffNote: $checkedConvert('staff_note', (v) => v as String?),
      sanctionCode: $checkedConvert('sanction_code', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'occurredOn': 'occurred_on',
    'infractionCode': 'infraction_code',
    'staffNote': 'staff_note',
    'sanctionCode': 'sanction_code',
  },
);

Map<String, dynamic> _$DisciplineRecordWriteToJson(
  DisciplineRecordWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'occurred_on': instance.occurredOn,
  'infraction_code': instance.infractionCode,
  'description': instance.description,
  'staff_note': ?instance.staffNote,
  'sanction_code': ?instance.sanctionCode,
};
