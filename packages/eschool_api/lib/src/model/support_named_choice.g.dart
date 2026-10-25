// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_named_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportNamedChoice _$SupportNamedChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportNamedChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = SupportNamedChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SupportNamedChoiceToJson(SupportNamedChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
