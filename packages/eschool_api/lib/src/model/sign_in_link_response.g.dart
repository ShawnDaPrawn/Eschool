// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_link_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinkResponse _$SignInLinkResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInLinkResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SignInLinkResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => SignInLink.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
      );
      return val;
    });

Map<String, dynamic> _$SignInLinkResponseToJson(SignInLinkResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': _onlyNullToJson(instance.code),
    };
