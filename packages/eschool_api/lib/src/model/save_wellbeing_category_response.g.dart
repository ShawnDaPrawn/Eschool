// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'save_wellbeing_category_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SaveWellbeingCategoryResponse _$SaveWellbeingCategoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SaveWellbeingCategoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SaveWellbeingCategoryResponse(
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

Map<String, dynamic> _$SaveWellbeingCategoryResponseToJson(
  SaveWellbeingCategoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
