// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_link.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLink _$SignInLinkFromJson(Map<String, dynamic> json) => $checkedCreate(
  'SignInLink',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'provider',
        'label',
        'email',
        'linked_at',
        'last_used_at',
      ],
    );
    final val = SignInLink(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      provider: $checkedConvert(
        'provider',
        (v) => $enumDecode(
          _$SignInProviderNameEnumMap,
          v,
          unknownValue: SignInProviderName.unknownDefaultOpenApi,
        ),
      ),
      label: $checkedConvert('label', (v) => v as String),
      email: $checkedConvert('email', (v) => v as String),
      linkedAt: $checkedConvert(
        'linked_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      lastUsedAt: $checkedConvert(
        'last_used_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'linkedAt': 'linked_at', 'lastUsedAt': 'last_used_at'},
);

Map<String, dynamic> _$SignInLinkToJson(SignInLink instance) =>
    <String, dynamic>{
      'id': instance.id,
      'provider': _$SignInProviderNameEnumMap[instance.provider]!,
      'label': instance.label,
      'email': instance.email,
      'linked_at': instance.linkedAt?.toIso8601String(),
      'last_used_at': instance.lastUsedAt?.toIso8601String(),
    };

const _$SignInProviderNameEnumMap = {
  SignInProviderName.google: 'google',
  SignInProviderName.microsoft: 'microsoft',
  SignInProviderName.unknownDefaultOpenApi: 'unknown_default_open_api',
};
