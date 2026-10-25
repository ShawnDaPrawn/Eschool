// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationChoice _$ConsultationChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ConsultationChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = ConsultationChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ConsultationChoiceToJson(ConsultationChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
