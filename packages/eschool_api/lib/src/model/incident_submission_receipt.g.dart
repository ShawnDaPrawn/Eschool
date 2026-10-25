// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_submission_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentSubmissionReceipt _$IncidentSubmissionReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentSubmissionReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['received', 'submitted_at', 'emergency_guidance'],
    );
    final val = IncidentSubmissionReceipt(
      received: $checkedConvert('received', (v) => v as bool),
      submittedAt: $checkedConvert(
        'submitted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      emergencyGuidance: $checkedConvert(
        'emergency_guidance',
        (v) => v as String,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'submittedAt': 'submitted_at',
    'emergencyGuidance': 'emergency_guidance',
  },
);

Map<String, dynamic> _$IncidentSubmissionReceiptToJson(
  IncidentSubmissionReceipt instance,
) => <String, dynamic>{
  'received': instance.received,
  'submitted_at': instance.submittedAt?.toIso8601String(),
  'emergency_guidance': instance.emergencyGuidance,
};
