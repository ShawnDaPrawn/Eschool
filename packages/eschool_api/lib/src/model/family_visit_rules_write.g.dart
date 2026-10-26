// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_rules_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitRulesWrite _$FamilyVisitRulesWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyVisitRulesWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'max_window_minutes',
        'max_future_days',
        'scan_max_age_seconds',
      ],
    );
    final val = FamilyVisitRulesWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      maxWindowMinutes: $checkedConvert(
        'max_window_minutes',
        (v) => (v as num).toInt(),
      ),
      maxFutureDays: $checkedConvert(
        'max_future_days',
        (v) => (v as num).toInt(),
      ),
      scanMaxAgeSeconds: $checkedConvert(
        'scan_max_age_seconds',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'maxWindowMinutes': 'max_window_minutes',
    'maxFutureDays': 'max_future_days',
    'scanMaxAgeSeconds': 'scan_max_age_seconds',
  },
);

Map<String, dynamic> _$FamilyVisitRulesWriteToJson(
  FamilyVisitRulesWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'max_window_minutes': instance.maxWindowMinutes,
  'max_future_days': instance.maxFutureDays,
  'scan_max_age_seconds': instance.scanMaxAgeSeconds,
};
