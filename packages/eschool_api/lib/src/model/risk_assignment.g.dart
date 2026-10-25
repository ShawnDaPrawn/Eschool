// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignment _$RiskAssignmentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskAssignment',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'starts_on',
            'ends_on',
            'revoked',
            'revision',
            'professional_id',
          ],
        );
        final val = RiskAssignment(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          revoked: $checkedConvert('revoked', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          professionalId: $checkedConvert(
            'professional_id',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
        'professionalId': 'professional_id',
      },
    );

Map<String, dynamic> _$RiskAssignmentToJson(RiskAssignment instance) =>
    <String, dynamic>{
      'id': instance.id,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'revoked': instance.revoked,
      'revision': instance.revision,
      'professional_id': instance.professionalId,
    };
