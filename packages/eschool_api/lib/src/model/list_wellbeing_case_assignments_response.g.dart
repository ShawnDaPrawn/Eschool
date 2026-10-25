// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_case_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingCaseAssignmentsResponse
_$ListWellbeingCaseAssignmentsResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ListWellbeingCaseAssignmentsResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = ListWellbeingCaseAssignmentsResponse(
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

Map<String, dynamic> _$ListWellbeingCaseAssignmentsResponseToJson(
  ListWellbeingCaseAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
