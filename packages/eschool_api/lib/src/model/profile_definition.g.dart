// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_definition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileDefinition _$ProfileDefinitionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProfileDefinition',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'category_id',
            'key',
            'label',
            'type',
            'validation',
            'options',
            'visible_to',
            'editable_by',
            'required',
            'locked',
            'sensitive',
            'encrypted',
            'hidden',
            'sort_order',
          ],
        );
        final val = ProfileDefinition(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
          key: $checkedConvert('key', (v) => v as String),
          label: $checkedConvert('label', (v) => v as String),
          type: $checkedConvert('type', (v) => v as String),
          validation: $checkedConvert(
            'validation',
            (v) => ProfileValidationRules.fromJson(v as Map<String, dynamic>),
          ),
          options: $checkedConvert(
            'options',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          visibleTo: $checkedConvert(
            'visible_to',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          editableBy: $checkedConvert(
            'editable_by',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          required_: $checkedConvert('required', (v) => v as bool),
          locked: $checkedConvert('locked', (v) => v as bool),
          sensitive: $checkedConvert('sensitive', (v) => v as bool),
          encrypted: $checkedConvert('encrypted', (v) => v as bool),
          hidden: $checkedConvert('hidden', (v) => v as bool),
          sortOrder: $checkedConvert('sort_order', (v) => (v as num).toInt()),
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

Map<String, dynamic> _$ProfileDefinitionToJson(ProfileDefinition instance) =>
    <String, dynamic>{
      'id': instance.id,
      'category_id': instance.categoryId,
      'key': instance.key,
      'label': instance.label,
      'type': instance.type,
      'validation': instance.validation.toJson(),
      'options': instance.options,
      'visible_to': instance.visibleTo,
      'editable_by': instance.editableBy,
      'required': instance.required_,
      'locked': instance.locked,
      'sensitive': instance.sensitive,
      'encrypted': instance.encrypted,
      'hidden': instance.hidden,
      'sort_order': instance.sortOrder,
    };
