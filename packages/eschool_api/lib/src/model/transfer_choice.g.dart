// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferChoice _$TransferChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TransferChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['key', 'label', 'detail']);
      final val = TransferChoice(
        key: $checkedConvert('key', (v) => v as String),
        label: $checkedConvert('label', (v) => v as String),
        detail: $checkedConvert('detail', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$TransferChoiceToJson(TransferChoice instance) =>
    <String, dynamic>{
      'key': instance.key,
      'label': instance.label,
      'detail': instance.detail,
    };
