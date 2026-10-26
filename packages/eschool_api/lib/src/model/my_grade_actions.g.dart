// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_actions.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeActions _$MyGradeActionsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MyGradeActions', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['registers', 'messages', 'interventions'],
      );
      final val = MyGradeActions(
        registers: $checkedConvert('registers', (v) => v as bool),
        messages: $checkedConvert('messages', (v) => v as bool),
        interventions: $checkedConvert('interventions', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$MyGradeActionsToJson(MyGradeActions instance) =>
    <String, dynamic>{
      'registers': instance.registers,
      'messages': instance.messages,
      'interventions': instance.interventions,
    };
