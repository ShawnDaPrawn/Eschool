// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_parent_fee_accounts_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListParentFeeAccountsResponse _$ListParentFeeAccountsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListParentFeeAccountsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListParentFeeAccountsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ParentFeeAccountsPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListParentFeeAccountsResponseToJson(
  ListParentFeeAccountsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
