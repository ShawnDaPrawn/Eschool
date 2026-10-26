// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_rules.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitRules _$FamilyVisitRulesFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyVisitRules',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'max_window_minutes',
            'max_future_days',
            'scan_max_age_seconds',
            'revision',
            'staff_approval_required',
            'collector_check_required',
          ],
        );
        final val = FamilyVisitRules(
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
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          staffApprovalRequired: $checkedConvert(
            'staff_approval_required',
            (v) => v as bool,
          ),
          collectorCheckRequired: $checkedConvert(
            'collector_check_required',
            (v) => v as bool,
          ),
          id: $checkedConvert('id', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'maxWindowMinutes': 'max_window_minutes',
        'maxFutureDays': 'max_future_days',
        'scanMaxAgeSeconds': 'scan_max_age_seconds',
        'staffApprovalRequired': 'staff_approval_required',
        'collectorCheckRequired': 'collector_check_required',
      },
    );

Map<String, dynamic> _$FamilyVisitRulesToJson(FamilyVisitRules instance) =>
    <String, dynamic>{
      'max_window_minutes': instance.maxWindowMinutes,
      'max_future_days': instance.maxFutureDays,
      'scan_max_age_seconds': instance.scanMaxAgeSeconds,
      'revision': instance.revision,
      'staff_approval_required': instance.staffApprovalRequired,
      'collector_check_required': instance.collectorCheckRequired,
      'id': ?instance.id,
    };
