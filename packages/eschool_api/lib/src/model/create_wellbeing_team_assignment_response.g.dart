// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_wellbeing_team_assignment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateWellbeingTeamAssignmentResponse
_$CreateWellbeingTeamAssignmentResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CreateWellbeingTeamAssignmentResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = CreateWellbeingTeamAssignmentResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => WellbeingTeamReceipt.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$CreateWellbeingTeamAssignmentResponseToJson(
  CreateWellbeingTeamAssignmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
