// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'save_wellbeing_case_assignment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SaveWellbeingCaseAssignmentResponse
_$SaveWellbeingCaseAssignmentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SaveWellbeingCaseAssignmentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SaveWellbeingCaseAssignmentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCaseAssignmentReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SaveWellbeingCaseAssignmentResponseToJson(
  SaveWellbeingCaseAssignmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
