// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_form_field.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileFormField _$ProfileFormFieldFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ProfileFormField', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'key',
          'label',
          'type',
          'validation',
          'options',
          'required',
          'locked',
          'editable',
          'sensitive',
          'value',
        ],
      );
      final val = ProfileFormField(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
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
        required_: $checkedConvert('required', (v) => v as bool),
        locked: $checkedConvert('locked', (v) => v as bool),
        editable: $checkedConvert('editable', (v) => v as bool),
        sensitive: $checkedConvert('sensitive', (v) => v as bool),
        value: $checkedConvert(
          'value',
          (v) => v == null
              ? null
              : ProfileValue.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'required_': 'required'});

Map<String, dynamic> _$ProfileFormFieldToJson(ProfileFormField instance) =>
    <String, dynamic>{
      'id': instance.id,
      'key': instance.key,
      'label': instance.label,
      'type': instance.type,
      'validation': instance.validation.toJson(),
      'options': instance.options,
      'required': instance.required_,
      'locked': instance.locked,
      'editable': instance.editable,
      'sensitive': instance.sensitive,
      'value': instance.value?.toJson(),
    };
