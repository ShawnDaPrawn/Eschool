// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_set_question_diagram.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSetQuestionDiagram _$PracticeSetQuestionDiagramFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeSetQuestionDiagram', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['width', 'height']);
  final val = PracticeSetQuestionDiagram(
    width: $checkedConvert('width', (v) => (v as num).toInt()),
    height: $checkedConvert('height', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PracticeSetQuestionDiagramToJson(
  PracticeSetQuestionDiagram instance,
) => <String, dynamic>{'width': instance.width, 'height': instance.height};
