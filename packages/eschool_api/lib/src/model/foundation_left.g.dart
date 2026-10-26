// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_left.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationLeft _$FoundationLeftFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FoundationLeft', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['left']);
      final val = FoundationLeft(
        left: $checkedConvert('left', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$FoundationLeftToJson(FoundationLeft instance) =>
    <String, dynamic>{'left': instance.left};
