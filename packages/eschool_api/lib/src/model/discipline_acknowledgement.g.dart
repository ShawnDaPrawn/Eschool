// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_acknowledgement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineAcknowledgement _$DisciplineAcknowledgementFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineAcknowledgement',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['record_id', 'revision', 'acknowledged_at'],
    );
    final val = DisciplineAcknowledgement(
      recordId: $checkedConvert('record_id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      acknowledgedAt: $checkedConvert(
        'acknowledged_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'recordId': 'record_id',
    'acknowledgedAt': 'acknowledged_at',
  },
);

Map<String, dynamic> _$DisciplineAcknowledgementToJson(
  DisciplineAcknowledgement instance,
) => <String, dynamic>{
  'record_id': instance.recordId,
  'revision': instance.revision,
  'acknowledged_at': instance.acknowledgedAt.toIso8601String(),
};
