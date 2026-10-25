// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_option_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationOptionInput _$ConsultationOptionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationOptionInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['label']);
  final val = ConsultationOptionInput(
    label: $checkedConvert('label', (v) => v as String),
    statement: $checkedConvert('statement', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$ConsultationOptionInputToJson(
  ConsultationOptionInput instance,
) => <String, dynamic>{
  'label': instance.label,
  'statement': ?instance.statement,
};
