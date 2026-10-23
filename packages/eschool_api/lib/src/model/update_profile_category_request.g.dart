// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_profile_category_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateProfileCategoryRequest _$UpdateProfileCategoryRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'UpdateProfileCategoryRequest',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['profile_type', 'key', 'label']);
    final val = UpdateProfileCategoryRequest(
      profileType: $checkedConvert('profile_type', (v) => v as String),
      key: $checkedConvert('key', (v) => v as String),
      label: $checkedConvert('label', (v) => v as String),
      sortOrder: $checkedConvert('sort_order', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'profileType': 'profile_type', 'sortOrder': 'sort_order'},
);

Map<String, dynamic> _$UpdateProfileCategoryRequestToJson(
  UpdateProfileCategoryRequest instance,
) => <String, dynamic>{
  'profile_type': instance.profileType,
  'key': instance.key,
  'label': instance.label,
  'sort_order': ?instance.sortOrder,
};
