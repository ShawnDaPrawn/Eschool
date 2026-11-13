// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_link_removed_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinkRemovedResponse _$SignInLinkRemovedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInLinkRemovedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SignInLinkRemovedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SignInLinkRemovedResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
  );
  return val;
});

Map<String, dynamic> _$SignInLinkRemovedResponseToJson(
  SignInLinkRemovedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _onlyNullToJson(instance.code),
};
