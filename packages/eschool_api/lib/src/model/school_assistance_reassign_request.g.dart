// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_reassign_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceReassignRequest _$SchoolAssistanceReassignRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceReassignRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'assigned_user_id',
        'reason',
      ],
    );
    final val = SchoolAssistanceReassignRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      assignedUserId: $checkedConvert(
        'assigned_user_id',
        (v) => (v as num).toInt(),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'assignedUserId': 'assigned_user_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceReassignRequestToJson(
  SchoolAssistanceReassignRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'assigned_user_id': instance.assignedUserId,
  'reason': instance.reason,
};
