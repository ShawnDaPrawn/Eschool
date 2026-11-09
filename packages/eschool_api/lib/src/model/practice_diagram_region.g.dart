// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_diagram_region.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeDiagramRegion _$PracticeDiagramRegionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeDiagramRegion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'x', 'y']);
  final val = PracticeDiagramRegion(
    id: $checkedConvert('id', (v) => v as String),
    x: $checkedConvert('x', (v) => (v as num).toInt()),
    y: $checkedConvert('y', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PracticeDiagramRegionToJson(
  PracticeDiagramRegion instance,
) => <String, dynamic>{'id': instance.id, 'x': instance.x, 'y': instance.y};
