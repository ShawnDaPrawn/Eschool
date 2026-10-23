// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_definition_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileDefinitionResponse _$ProfileDefinitionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProfileDefinitionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ProfileDefinitionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ProfileDefinition.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ProfileDefinitionResponseToJson(
  ProfileDefinitionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
