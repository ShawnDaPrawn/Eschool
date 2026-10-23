// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileConfigurationResponse _$ProfileConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProfileConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ProfileConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                ProfileCategoryDefinition.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ProfileConfigurationResponseToJson(
  ProfileConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.map((e) => e.toJson()).toList(),
  'code': instance.code,
};
