// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_cases_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportCasesResponse _$SupportCasesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportCasesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportCasesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportCases.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportCasesResponseToJson(
  SupportCasesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
