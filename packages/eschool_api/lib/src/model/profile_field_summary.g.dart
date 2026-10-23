// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_field_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileFieldSummary _$ProfileFieldSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProfileFieldSummary',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'profile_type',
            'key',
            'label',
            'type',
            'required',
            'locked',
            'category_key',
            'category_label',
          ],
        );
        final val = ProfileFieldSummary(
          profileType: $checkedConvert('profile_type', (v) => v as String),
          key: $checkedConvert('key', (v) => v as String),
          label: $checkedConvert('label', (v) => v as String),
          type: $checkedConvert('type', (v) => v as String),
          required_: $checkedConvert('required', (v) => v as bool),
          locked: $checkedConvert('locked', (v) => v as bool),
          categoryKey: $checkedConvert('category_key', (v) => v as String),
          categoryLabel: $checkedConvert('category_label', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'profileType': 'profile_type',
        'required_': 'required',
        'categoryKey': 'category_key',
        'categoryLabel': 'category_label',
      },
    );

Map<String, dynamic> _$ProfileFieldSummaryToJson(
  ProfileFieldSummary instance,
) => <String, dynamic>{
  'profile_type': instance.profileType,
  'key': instance.key,
  'label': instance.label,
  'type': instance.type,
  'required': instance.required_,
  'locked': instance.locked,
  'category_key': instance.categoryKey,
  'category_label': instance.categoryLabel,
};
