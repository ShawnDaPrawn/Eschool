// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'save_wellbeing_team_assignment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SaveWellbeingTeamAssignmentResponse
_$SaveWellbeingTeamAssignmentResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SaveWellbeingTeamAssignmentResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SaveWellbeingTeamAssignmentResponse(
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

Map<String, dynamic> _$SaveWellbeingTeamAssignmentResponseToJson(
  SaveWellbeingTeamAssignmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
