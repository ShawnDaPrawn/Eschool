// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'personal_wallet_statement_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PersonalWalletStatementResponse _$PersonalWalletStatementResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PersonalWalletStatementResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PersonalWalletStatementResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletStatement.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PersonalWalletStatementResponseToJson(
  PersonalWalletStatementResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
