// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_claim_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceClaimRequest _$SchoolAssistanceClaimRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceClaimRequest',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'expected_revision']);
    final val = SchoolAssistanceClaimRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$SchoolAssistanceClaimRequestToJson(
  SchoolAssistanceClaimRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
};
