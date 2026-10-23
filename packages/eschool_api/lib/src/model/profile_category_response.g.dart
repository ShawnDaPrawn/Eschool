// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_category_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileCategoryResponse _$ProfileCategoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProfileCategoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ProfileCategoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ProfileCategoryDefinition.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ProfileCategoryResponseToJson(
  ProfileCategoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
