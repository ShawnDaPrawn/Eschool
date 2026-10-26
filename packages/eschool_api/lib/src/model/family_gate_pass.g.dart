// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_pass.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGatePass _$FamilyGatePassFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyGatePass',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'requested_by',
        'approved_by',
        'collector',
        'reason',
        'starts_at',
        'ends_at',
        'status',
        'revision',
        'collected_at',
      ],
    );
    final val = FamilyGatePass(
      id: $checkedConvert('id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      requestedBy: $checkedConvert('requested_by', (v) => (v as num).toInt()),
      approvedBy: $checkedConvert('approved_by', (v) => (v as num?)?.toInt()),
      collector: $checkedConvert('collector', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      status: $checkedConvert('status', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      collectedAt: $checkedConvert(
        'collected_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      learnerName: $checkedConvert('learner_name', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'requestedBy': 'requested_by',
    'approvedBy': 'approved_by',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'collectedAt': 'collected_at',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$FamilyGatePassToJson(FamilyGatePass instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'requested_by': instance.requestedBy,
      'approved_by': instance.approvedBy,
      'collector': instance.collector,
      'reason': instance.reason,
      'starts_at': instance.startsAt.toIso8601String(),
      'ends_at': instance.endsAt.toIso8601String(),
      'status': instance.status,
      'revision': instance.revision,
      'collected_at': instance.collectedAt?.toIso8601String(),
      'learner_name': ?instance.learnerName,
    };
