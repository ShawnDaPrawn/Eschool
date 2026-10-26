// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'grant_foundation_authorization_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GrantFoundationAuthorizationResponse
_$GrantFoundationAuthorizationResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GrantFoundationAuthorizationResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GrantFoundationAuthorizationResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => FoundationAuthorization.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GrantFoundationAuthorizationResponseToJson(
  GrantFoundationAuthorizationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
