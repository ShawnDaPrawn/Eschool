// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'set_wellbeing_case_status_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SetWellbeingCaseStatusResponse _$SetWellbeingCaseStatusResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SetWellbeingCaseStatusResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SetWellbeingCaseStatusResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCaseStatusReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SetWellbeingCaseStatusResponseToJson(
  SetWellbeingCaseStatusResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
