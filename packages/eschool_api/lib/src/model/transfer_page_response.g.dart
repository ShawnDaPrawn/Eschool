// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferPageResponse _$TransferPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TransferPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TransferPage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TransferPageResponseToJson(
  TransferPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
