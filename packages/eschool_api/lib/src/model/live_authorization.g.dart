// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_authorization.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LiveAuthorization _$LiveAuthorizationFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LiveAuthorization', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['auth']);
      final val = LiveAuthorization(
        auth: $checkedConvert('auth', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$LiveAuthorizationToJson(LiveAuthorization instance) =>
    <String, dynamic>{'auth': instance.auth};
