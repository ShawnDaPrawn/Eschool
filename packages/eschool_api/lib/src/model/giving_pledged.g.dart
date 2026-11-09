// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_pledged.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingPledged _$GivingPledgedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GivingPledged', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'reference']);
      final val = GivingPledged(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        reference: $checkedConvert('reference', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$GivingPledgedToJson(GivingPledged instance) =>
    <String, dynamic>{'id': instance.id, 'reference': instance.reference};
