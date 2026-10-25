// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_choice_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferChoicePageResponse _$TransferChoicePageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferChoicePageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TransferChoicePageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TransferChoicePage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TransferChoicePageResponseToJson(
  TransferChoicePageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
