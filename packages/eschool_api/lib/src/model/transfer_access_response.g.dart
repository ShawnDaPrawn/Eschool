// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferAccessResponse _$TransferAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TransferAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TransferAccess.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TransferAccessResponseToJson(
  TransferAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
