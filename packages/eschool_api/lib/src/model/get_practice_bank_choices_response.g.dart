// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_bank_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeBankChoicesResponse _$GetPracticeBankChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeBankChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeBankChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeBankChoices.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeBankChoicesResponseToJson(
  GetPracticeBankChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
