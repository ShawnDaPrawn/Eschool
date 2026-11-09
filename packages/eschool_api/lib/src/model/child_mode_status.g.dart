// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_status.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeStatus _$ChildModeStatusFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ChildModeStatus',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'learner_id',
            'first_name',
            'available',
            'reason',
            'message',
            'pin_set',
            'pin_locked_until',
            'opens',
            'session_minutes',
          ],
        );
        final val = ChildModeStatus(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          firstName: $checkedConvert('first_name', (v) => v as String),
          available: $checkedConvert('available', (v) => v as bool),
          reason: $checkedConvert(
            'reason',
            (v) => $enumDecodeNullable(
              _$ChildModeStatusReasonEnumEnumMap,
              v,
              unknownValue: ChildModeStatusReasonEnum.unknownDefaultOpenApi,
            ),
          ),
          message: $checkedConvert('message', (v) => v as String?),
          pinSet: $checkedConvert('pin_set', (v) => v as bool),
          pinLockedUntil: $checkedConvert(
            'pin_locked_until',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          opens: $checkedConvert(
            'opens',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => $enumDecode(
                    _$ChildModeStatusOpensEnumEnumMap,
                    e,
                    unknownValue:
                        ChildModeStatusOpensEnum.unknownDefaultOpenApi,
                  ),
                )
                .toList(),
          ),
          sessionMinutes: $checkedConvert(
            'session_minutes',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'firstName': 'first_name',
        'pinSet': 'pin_set',
        'pinLockedUntil': 'pin_locked_until',
        'sessionMinutes': 'session_minutes',
      },
    );

Map<String, dynamic> _$ChildModeStatusToJson(ChildModeStatus instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'first_name': instance.firstName,
      'available': instance.available,
      'reason': _$ChildModeStatusReasonEnumEnumMap[instance.reason],
      'message': instance.message,
      'pin_set': instance.pinSet,
      'pin_locked_until': instance.pinLockedUntil?.toIso8601String(),
      'opens': instance.opens
          .map((e) => _$ChildModeStatusOpensEnumEnumMap[e]!)
          .toList(),
      'session_minutes': instance.sessionMinutes,
    };

const _$ChildModeStatusReasonEnumEnumMap = {
  ChildModeStatusReasonEnum.notApproving: 'not_approving',
  ChildModeStatusReasonEnum.guardianUnverified: 'guardian_unverified',
  ChildModeStatusReasonEnum.approvalNeeded: 'approval_needed',
  ChildModeStatusReasonEnum.earlyYears: 'early_years',
  ChildModeStatusReasonEnum.highSchool: 'high_school',
  ChildModeStatusReasonEnum.gradeUnknown: 'grade_unknown',
  ChildModeStatusReasonEnum.notMember: 'not_member',
  ChildModeStatusReasonEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ChildModeStatusOpensEnumEnumMap = {
  ChildModeStatusOpensEnum.myDay: 'my_day',
  ChildModeStatusOpensEnum.practice: 'practice',
  ChildModeStatusOpensEnum.tutor: 'tutor',
  ChildModeStatusOpensEnum.library_: 'library',
  ChildModeStatusOpensEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
