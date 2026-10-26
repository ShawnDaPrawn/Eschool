// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointOption _$TutorCheckpointOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorCheckpointOption', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'text']);
  final val = TutorCheckpointOption(
    id: $checkedConvert('id', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$TutorCheckpointOptionToJson(
  TutorCheckpointOption instance,
) => <String, dynamic>{'id': instance.id, 'text': instance.text};
