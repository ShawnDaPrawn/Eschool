// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_prepare_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferPrepareInput _$TransferPrepareInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TransferPrepareInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'learner_id',
        'guardian_id',
        'to_school_id',
        'external_school',
        'last_attended_on',
        'starts_on',
        'report_ids',
        'document_ids',
      ],
    );
    final val = TransferPrepareInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      guardianId: $checkedConvert('guardian_id', (v) => (v as num).toInt()),
      toSchoolId: $checkedConvert('to_school_id', (v) => (v as num?)?.toInt()),
      externalSchool: $checkedConvert('external_school', (v) => v as String?),
      lastAttendedOn: $checkedConvert('last_attended_on', (v) => v as String),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      reportIds: $checkedConvert(
        'report_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      documentIds: $checkedConvert(
        'document_ids',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerId': 'learner_id',
    'guardianId': 'guardian_id',
    'toSchoolId': 'to_school_id',
    'externalSchool': 'external_school',
    'lastAttendedOn': 'last_attended_on',
    'startsOn': 'starts_on',
    'reportIds': 'report_ids',
    'documentIds': 'document_ids',
  },
);

Map<String, dynamic> _$TransferPrepareInputToJson(
  TransferPrepareInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
  'guardian_id': instance.guardianId,
  'to_school_id': instance.toSchoolId,
  'external_school': instance.externalSchool,
  'last_attended_on': instance.lastAttendedOn,
  'starts_on': instance.startsOn,
  'report_ids': instance.reportIds,
  'document_ids': instance.documentIds,
};
