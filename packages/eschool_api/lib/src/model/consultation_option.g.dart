// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationOption _$ConsultationOptionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ConsultationOption', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'label', 'statement']);
      final val = ConsultationOption(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        label: $checkedConvert('label', (v) => v as String),
        statement: $checkedConvert('statement', (v) => v as String?),
      );
      return val;
    });

Map<String, dynamic> _$ConsultationOptionToJson(ConsultationOption instance) =>
    <String, dynamic>{
      'id': instance.id,
      'label': instance.label,
      'statement': instance.statement,
    };
