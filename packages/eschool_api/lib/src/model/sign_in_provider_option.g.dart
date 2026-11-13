// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_provider_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInProviderOption _$SignInProviderOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInProviderOption', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['provider', 'label']);
  final val = SignInProviderOption(
    provider: $checkedConvert(
      'provider',
      (v) => $enumDecode(
        _$SignInProviderNameEnumMap,
        v,
        unknownValue: SignInProviderName.unknownDefaultOpenApi,
      ),
    ),
    label: $checkedConvert('label', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$SignInProviderOptionToJson(
  SignInProviderOption instance,
) => <String, dynamic>{
  'provider': _$SignInProviderNameEnumMap[instance.provider]!,
  'label': instance.label,
};

const _$SignInProviderNameEnumMap = {
  SignInProviderName.google: 'google',
  SignInProviderName.microsoft: 'microsoft',
  SignInProviderName.unknownDefaultOpenApi: 'unknown_default_open_api',
};
