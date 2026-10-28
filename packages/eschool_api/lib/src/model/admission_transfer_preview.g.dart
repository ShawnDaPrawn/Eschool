// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferPreview _$AdmissionTransferPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionTransferPreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'ticket_id',
        'revision',
        'recipient_id',
        'recipient_name',
        'kind',
        'event_name',
      ],
    );
    final val = AdmissionTransferPreview(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      ticketId: $checkedConvert('ticket_id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      recipientId: $checkedConvert('recipient_id', (v) => (v as num).toInt()),
      recipientName: $checkedConvert('recipient_name', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AdmissionTransferPreviewKindEnumEnumMap,
          v,
          unknownValue: AdmissionTransferPreviewKindEnum.unknownDefaultOpenApi,
        ),
      ),
      eventName: $checkedConvert('event_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'ticketId': 'ticket_id',
    'recipientId': 'recipient_id',
    'recipientName': 'recipient_name',
    'eventName': 'event_name',
  },
);

Map<String, dynamic> _$AdmissionTransferPreviewToJson(
  AdmissionTransferPreview instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'ticket_id': instance.ticketId,
  'revision': instance.revision,
  'recipient_id': instance.recipientId,
  'recipient_name': instance.recipientName,
  'kind': _$AdmissionTransferPreviewKindEnumEnumMap[instance.kind]!,
  'event_name': instance.eventName,
};

const _$AdmissionTransferPreviewKindEnumEnumMap = {
  AdmissionTransferPreviewKindEnum.general: 'general',
  AdmissionTransferPreviewKindEnum.named: 'named',
  AdmissionTransferPreviewKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
