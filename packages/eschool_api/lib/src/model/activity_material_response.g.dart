// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialResponse _$ActivityMaterialResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityMaterialResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityMaterialResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityMaterial.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityMaterialResponseToJson(
  ActivityMaterialResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
