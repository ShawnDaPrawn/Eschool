// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'revoke_foundation_authorization_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RevokeFoundationAuthorizationResponse
_$RevokeFoundationAuthorizationResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RevokeFoundationAuthorizationResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = RevokeFoundationAuthorizationResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => FoundationRevoked.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$RevokeFoundationAuthorizationResponseToJson(
  RevokeFoundationAuthorizationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
