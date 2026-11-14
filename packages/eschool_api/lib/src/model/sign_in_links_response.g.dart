// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_links_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinksResponse _$SignInLinksResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInLinksResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SignInLinksResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => SignInLinksResponseData.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
      );
      return val;
    });

Map<String, dynamic> _$SignInLinksResponseToJson(
  SignInLinksResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _onlyNullToJson(instance.code),
};
