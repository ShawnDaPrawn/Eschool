// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_form_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileFormCategory _$ProfileFormCategoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ProfileFormCategory', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'key', 'label', 'fields']);
      final val = ProfileFormCategory(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        key: $checkedConvert('key', (v) => v as String),
        label: $checkedConvert('label', (v) => v as String),
        fields: $checkedConvert(
          'fields',
          (v) => (v as List<dynamic>)
              .map((e) => ProfileFormField.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ProfileFormCategoryToJson(
  ProfileFormCategory instance,
) => <String, dynamic>{
  'id': instance.id,
  'key': instance.key,
  'label': instance.label,
  'fields': instance.fields.map((e) => e.toJson()).toList(),
};
