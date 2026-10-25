// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionReference _$RecognitionReferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionReference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision']);
  final val = RecognitionReference(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$RecognitionReferenceToJson(
  RecognitionReference instance,
) => <String, dynamic>{'id': instance.id, 'revision': instance.revision};
