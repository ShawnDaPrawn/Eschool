// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_profile_field_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateProfileFieldRequest _$CreateProfileFieldRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CreateProfileFieldRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'category_id',
        'key',
        'label',
        'type',
        'visible_to',
        'editable_by',
      ],
    );
    final val = CreateProfileFieldRequest(
      categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
      key: $checkedConvert('key', (v) => v as String),
      label: $checkedConvert('label', (v) => v as String),
      type: $checkedConvert('type', (v) => v as String),
      validation: $checkedConvert(
        'validation',
        (v) => v == null
            ? null
            : ProfileValidationRules.fromJson(v as Map<String, dynamic>),
      ),
      options: $checkedConvert(
        'options',
        (v) => (v as List<dynamic>?)?.map((e) => e as String).toList(),
      ),
      visibleTo: $checkedConvert(
        'visible_to',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      editableBy: $checkedConvert(
        'editable_by',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      required_: $checkedConvert('required', (v) => v as bool?),
      sensitive: $checkedConvert('sensitive', (v) => v as bool?),
      encrypted: $checkedConvert('encrypted', (v) => v as bool?),
      hidden: $checkedConvert('hidden', (v) => v as bool?),
      sortOrder: $checkedConvert('sort_order', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'categoryId': 'category_id',
    'visibleTo': 'visible_to',
    'editableBy': 'editable_by',
    'required_': 'required',
    'sortOrder': 'sort_order',
  },
);

Map<String, dynamic> _$CreateProfileFieldRequestToJson(
  CreateProfileFieldRequest instance,
) => <String, dynamic>{
  'category_id': instance.categoryId,
  'key': instance.key,
  'label': instance.label,
  'type': instance.type,
  'validation': ?instance.validation?.toJson(),
  'options': ?instance.options,
  'visible_to': instance.visibleTo,
  'editable_by': instance.editableBy,
  'required': ?instance.required_,
  'sensitive': ?instance.sensitive,
  'encrypted': ?instance.encrypted,
  'hidden': ?instance.hidden,
  'sort_order': ?instance.sortOrder,
};
