// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_diagram.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeDiagram _$PracticeDiagramFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeDiagram', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'width', 'height']);
      final val = PracticeDiagram(
        id: $checkedConvert('id', (v) => v as String),
        width: $checkedConvert('width', (v) => (v as num).toInt()),
        height: $checkedConvert('height', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$PracticeDiagramToJson(PracticeDiagram instance) =>
    <String, dynamic>{
      'id': instance.id,
      'width': instance.width,
      'height': instance.height,
    };
