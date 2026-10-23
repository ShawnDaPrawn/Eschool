// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_value.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileValue _$ProfileValueFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ProfileValue', json, ($checkedConvert) {
      final val = ProfileValue(
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

Map<String, dynamic> _$ProfileValueToJson(ProfileValue instance) =>
    <String, dynamic>{
      'text': ?instance.text,
      'choices': ?instance.choices,
      'flag': ?instance.flag,
      'clear': ?instance.clear,
    };
