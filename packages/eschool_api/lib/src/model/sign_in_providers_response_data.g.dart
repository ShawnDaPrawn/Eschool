// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_providers_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInProvidersResponseData _$SignInProvidersResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInProvidersResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['providers']);
  final val = SignInProvidersResponseData(
    providers: $checkedConvert(
      'providers',
      (v) => (v as List<dynamic>)
          .map((e) => SignInProviderOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$SignInProvidersResponseDataToJson(
  SignInProvidersResponseData instance,
) => <String, dynamic>{
  'providers': instance.providers.map((e) => e.toJson()).toList(),
};
