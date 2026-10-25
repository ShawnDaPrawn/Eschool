// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_team_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingTeamAssignmentsResponse
_$ListWellbeingTeamAssignmentsResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ListWellbeingTeamAssignmentsResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = ListWellbeingTeamAssignmentsResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => WellbeingAssignmentPage.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ListWellbeingTeamAssignmentsResponseToJson(
  ListWellbeingTeamAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
