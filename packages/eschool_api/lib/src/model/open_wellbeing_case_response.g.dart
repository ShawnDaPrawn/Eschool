// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'open_wellbeing_case_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OpenWellbeingCaseResponse _$OpenWellbeingCaseResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('OpenWellbeingCaseResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = OpenWellbeingCaseResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCaseOpenReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$OpenWellbeingCaseResponseToJson(
  OpenWellbeingCaseResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
