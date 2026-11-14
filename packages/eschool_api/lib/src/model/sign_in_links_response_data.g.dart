// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_links_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinksResponseData _$SignInLinksResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInLinksResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['links', 'providers']);
  final val = SignInLinksResponseData(
    links: $checkedConvert(
      'links',
      (v) => (v as List<dynamic>)
          .map((e) => SignInLink.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    providers: $checkedConvert(
      'providers',
      (v) => (v as List<dynamic>)
          .map(
            (e) => SignInLinksResponseDataProvidersInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$SignInLinksResponseDataToJson(
  SignInLinksResponseData instance,
) => <String, dynamic>{
  'links': instance.links.map((e) => e.toJson()).toList(),
  'providers': instance.providers.map((e) => e.toJson()).toList(),
};
