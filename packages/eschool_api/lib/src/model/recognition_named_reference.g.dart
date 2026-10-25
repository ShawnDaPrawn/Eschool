// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_named_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNamedReference _$RecognitionNamedReferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionNamedReference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = RecognitionNamedReference(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$RecognitionNamedReferenceToJson(
  RecognitionNamedReference instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
