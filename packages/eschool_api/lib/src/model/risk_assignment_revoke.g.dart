// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignment_revoke.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignmentRevoke _$RiskAssignmentRevokeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RiskAssignmentRevoke',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'expected_revision',
      ],
    );
    final val = RiskAssignmentRevoke(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$RiskAssignmentRevokeToJson(
  RiskAssignmentRevoke instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'expected_revision': instance.expectedRevision,
};
