// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_pin_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModePinEntry _$ChildModePinEntryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ChildModePinEntry', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['pin']);
      final val = ChildModePinEntry(
        pin: $checkedConvert('pin', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ChildModePinEntryToJson(ChildModePinEntry instance) =>
    <String, dynamic>{'pin': instance.pin};
