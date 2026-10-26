// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_health_value.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyHealthValue _$FamilyHealthValueFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyHealthValue', json, ($checkedConvert) {
      final val = FamilyHealthValue(
        text: $checkedConvert('text', (v) => v as String?),
        choices: $checkedConvert(
          'choices',
          (v) => (v as List<dynamic>?)?.map((e) => e as String).toList(),
        ),
        flag: $checkedConvert('flag', (v) => v as bool?),
        clear: $checkedConvert('clear', (v) => v as bool?),
      );
      return val;
    });

Map<String, dynamic> _$FamilyHealthValueToJson(FamilyHealthValue instance) =>
    <String, dynamic>{
      'text': ?instance.text,
      'choices': ?instance.choices,
      'flag': ?instance.flag,
      'clear': ?instance.clear,
    };
