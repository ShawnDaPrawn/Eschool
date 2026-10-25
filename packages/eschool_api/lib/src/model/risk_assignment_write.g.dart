// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignment_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignmentWrite _$RiskAssignmentWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskAssignmentWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'session_year_id',
            'expected_revision',
            'starts_on',
            'ends_on',
          ],
        );
        final val = RiskAssignmentWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'expectedRevision': 'expected_revision',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$RiskAssignmentWriteToJson(
  RiskAssignmentWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'expected_revision': instance.expectedRevision,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
