// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_marking_audio.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkingAudio _$PracticeMarkingAudioFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeMarkingAudio', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['kept', 'mime', 'bytes', 'delete_after'],
  );
  final val = PracticeMarkingAudio(
    kept: $checkedConvert('kept', (v) => v as bool),
    mime: $checkedConvert('mime', (v) => v as String),
    bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
    deleteAfter: $checkedConvert('delete_after', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'deleteAfter': 'delete_after'});

Map<String, dynamic> _$PracticeMarkingAudioToJson(
  PracticeMarkingAudio instance,
) => <String, dynamic>{
  'kept': instance.kept,
  'mime': instance.mime,
  'bytes': instance.bytes,
  'delete_after': instance.deleteAfter,
};
