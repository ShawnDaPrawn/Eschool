// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerChoice _$PeerChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PeerChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = PeerChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PeerChoiceToJson(PeerChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
