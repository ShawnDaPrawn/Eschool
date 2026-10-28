// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'personal_wallet_index_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PersonalWalletIndexResponse _$PersonalWalletIndexResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PersonalWalletIndexResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PersonalWalletIndexResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PersonalWalletIndex.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PersonalWalletIndexResponseToJson(
  PersonalWalletIndexResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
