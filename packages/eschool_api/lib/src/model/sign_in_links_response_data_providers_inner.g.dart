// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_links_response_data_providers_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinksResponseDataProvidersInner
_$SignInLinksResponseDataProvidersInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInLinksResponseDataProvidersInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['provider', 'label', 'may_link']);
      final val = SignInLinksResponseDataProvidersInner(
        provider: $checkedConvert(
          'provider',
          (v) => $enumDecode(
            _$SignInProviderNameEnumMap,
            v,
            unknownValue: SignInProviderName.unknownDefaultOpenApi,
          ),
        ),
        label: $checkedConvert('label', (v) => v as String),
        mayLink: $checkedConvert('may_link', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'mayLink': 'may_link'});

Map<String, dynamic> _$SignInLinksResponseDataProvidersInnerToJson(
  SignInLinksResponseDataProvidersInner instance,
) => <String, dynamic>{
  'provider': _$SignInProviderNameEnumMap[instance.provider]!,
  'label': instance.label,
  'may_link': instance.mayLink,
};

const _$SignInProviderNameEnumMap = {
  SignInProviderName.google: 'google',
  SignInProviderName.microsoft: 'microsoft',
  SignInProviderName.unknownDefaultOpenApi: 'unknown_default_open_api',
};
