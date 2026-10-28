// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_ack.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferAck _$AdmissionTransferAckFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionTransferAck',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'ticket_id',
        'operation_id',
        'revision',
        'status',
      ],
    );
    final val = AdmissionTransferAck(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      ticketId: $checkedConvert('ticket_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$AdmissionTransferAckStatusEnumEnumMap,
          v,
          unknownValue: AdmissionTransferAckStatusEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'ticketId': 'ticket_id',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$AdmissionTransferAckToJson(
  AdmissionTransferAck instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'ticket_id': instance.ticketId,
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'status': _$AdmissionTransferAckStatusEnumEnumMap[instance.status]!,
};

const _$AdmissionTransferAckStatusEnumEnumMap = {
  AdmissionTransferAckStatusEnum.transferred: 'transferred',
  AdmissionTransferAckStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
