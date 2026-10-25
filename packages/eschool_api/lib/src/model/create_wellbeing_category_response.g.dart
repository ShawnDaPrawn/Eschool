// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_wellbeing_category_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateWellbeingCategoryResponse _$CreateWellbeingCategoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CreateWellbeingCategoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CreateWellbeingCategoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCategory.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CreateWellbeingCategoryResponseToJson(
  CreateWellbeingCategoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
