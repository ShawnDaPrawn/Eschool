// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_category_definition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileCategoryDefinition _$ProfileCategoryDefinitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProfileCategoryDefinition',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'profile_type',
        'key',
        'label',
        'sort_order',
        'fields',
      ],
    );
    final val = ProfileCategoryDefinition(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      profileType: $checkedConvert('profile_type', (v) => v as String),
      key: $checkedConvert('key', (v) => v as String),
      label: $checkedConvert('label', (v) => v as String),
      sortOrder: $checkedConvert('sort_order', (v) => (v as num).toInt()),
      fields: $checkedConvert(
        'fields',
        (v) => (v as List<dynamic>)
            .map((e) => ProfileDefinition.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'profileType': 'profile_type', 'sortOrder': 'sort_order'},
);

Map<String, dynamic> _$ProfileCategoryDefinitionToJson(
  ProfileCategoryDefinition instance,
) => <String, dynamic>{
  'id': instance.id,
  'profile_type': instance.profileType,
  'key': instance.key,
  'label': instance.label,
  'sort_order': instance.sortOrder,
  'fields': instance.fields.map((e) => e.toJson()).toList(),
};
