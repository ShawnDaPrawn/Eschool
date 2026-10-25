// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressOption _$ProgressOptionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ProgressOption', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = ProgressOption(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ProgressOptionToJson(ProgressOption instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
