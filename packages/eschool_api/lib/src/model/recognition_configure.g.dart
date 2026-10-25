// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_configure.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionConfigure _$RecognitionConfigureFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionConfigure',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'daily_staff_cap',
        'public_points_allowed',
        'principal_approval_required',
      ],
    );
    final val = RecognitionConfigure(
      operationId: $checkedConvert('operation_id', (v) => v as String),
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
    'operationId': 'operation_id',
    'dailyStaffCap': 'daily_staff_cap',
    'publicPointsAllowed': 'public_points_allowed',
    'principalApprovalRequired': 'principal_approval_required',
  },
);

Map<String, dynamic> _$RecognitionConfigureToJson(
  RecognitionConfigure instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'daily_staff_cap': instance.dailyStaffCap,
  'public_points_allowed': instance.publicPointsAllowed,
  'principal_approval_required': instance.principalApprovalRequired,
};
