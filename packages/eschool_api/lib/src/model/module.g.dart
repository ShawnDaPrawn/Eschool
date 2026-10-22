// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'module.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Module _$ModuleFromJson(Map<String, dynamic> json) =>
    $checkedCreate('Module', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['key', 'read_only']);
      final val = Module(
        key: $checkedConvert('key', (v) => v as String),
        readOnly: $checkedConvert('read_only', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'readOnly': 'read_only'});

Map<String, dynamic> _$ModuleToJson(Module instance) => <String, dynamic>{
  'key': instance.key,
  'read_only': instance.readOnly,
};
