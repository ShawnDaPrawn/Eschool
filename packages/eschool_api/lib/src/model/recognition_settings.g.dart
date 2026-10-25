// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionSettings _$RecognitionSettingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionSettings',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'revision',
            'daily_staff_cap',
            'public_points_allowed',
            'principal_approval_required',
          ],
        );
        final val = RecognitionSettings(
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          dailyStaffCap: $checkedConvert(
            'daily_staff_cap',
            (v) => (v as num).toInt(),
          ),
          publicPointsAllowed: $checkedConvert(
            'public_points_allowed',
            (v) => v as bool,
          ),
          principalApprovalRequired: $checkedConvert(
            'principal_approval_required',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'dailyStaffCap': 'daily_staff_cap',
        'publicPointsAllowed': 'public_points_allowed',
        'principalApprovalRequired': 'principal_approval_required',
      },
    );

Map<String, dynamic> _$RecognitionSettingsToJson(
  RecognitionSettings instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'daily_staff_cap': instance.dailyStaffCap,
  'public_points_allowed': instance.publicPointsAllowed,
  'principal_approval_required': instance.principalApprovalRequired,
};
