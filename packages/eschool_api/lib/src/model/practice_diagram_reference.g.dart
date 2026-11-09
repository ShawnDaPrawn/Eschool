// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_diagram_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeDiagramReference _$PracticeDiagramReferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeDiagramReference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id']);
  final val = PracticeDiagramReference(
    source_: $checkedConvert(
      'source',
      (v) => $enumDecodeNullable(
        _$PracticeDiagramReferenceSource_EnumEnumMap,
        v,
        unknownValue: PracticeDiagramReferenceSource_Enum.unknownDefaultOpenApi,
      ),
    ),
    id: $checkedConvert('id', (v) => v as String),
    width: $checkedConvert('width', (v) => (v as num?)?.toInt()),
    height: $checkedConvert('height', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'source_': 'source'});

Map<String, dynamic> _$PracticeDiagramReferenceToJson(
  PracticeDiagramReference instance,
) => <String, dynamic>{
  'source': ?_$PracticeDiagramReferenceSource_EnumEnumMap[instance.source_],
  'id': instance.id,
  'width': ?instance.width,
  'height': ?instance.height,
};

const _$PracticeDiagramReferenceSource_EnumEnumMap = {
  PracticeDiagramReferenceSource_Enum.school: 'school',
  PracticeDiagramReferenceSource_Enum.library_: 'library',
  PracticeDiagramReferenceSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
