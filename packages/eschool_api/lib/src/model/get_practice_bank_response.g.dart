// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_bank_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeBankResponse _$GetPracticeBankResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeBankResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeBankResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeBankPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeBankResponseToJson(
  GetPracticeBankResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
