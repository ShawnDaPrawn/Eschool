// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_staff_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskStaffChoice _$RiskStaffChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskStaffChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = RiskStaffChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$RiskStaffChoiceToJson(RiskStaffChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
