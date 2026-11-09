// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestOption _$PracticeTestOptionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeTestOption', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'text']);
      final val = PracticeTestOption(
        id: $checkedConvert('id', (v) => v as String),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PracticeTestOptionToJson(PracticeTestOption instance) =>
    <String, dynamic>{'id': instance.id, 'text': instance.text};
