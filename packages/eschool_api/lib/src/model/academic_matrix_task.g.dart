// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_matrix_task.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMatrixTask _$AcademicMatrixTaskFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicMatrixTask',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'canonical_task_id',
            'name',
            'max_mark',
            'component',
            'weight_bp',
            'questions',
          ],
        );
        final val = AcademicMatrixTask(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          canonicalTaskId: $checkedConvert(
            'canonical_task_id',
            (v) => (v as num).toInt(),
          ),
          name: $checkedConvert('name', (v) => v as String),
          maxMark: $checkedConvert('max_mark', (v) => v as String),
          component: $checkedConvert('component', (v) => v as String),
          weightBp: $checkedConvert('weight_bp', (v) => (v as num).toInt()),
          questions: $checkedConvert(
            'questions',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      AcademicTaskQuestion.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'canonicalTaskId': 'canonical_task_id',
        'maxMark': 'max_mark',
        'weightBp': 'weight_bp',
      },
    );

Map<String, dynamic> _$AcademicMatrixTaskToJson(AcademicMatrixTask instance) =>
    <String, dynamic>{
      'id': instance.id,
      'canonical_task_id': instance.canonicalTaskId,
      'name': instance.name,
      'max_mark': instance.maxMark,
      'component': instance.component,
      'weight_bp': instance.weightBp,
      'questions': instance.questions.map((e) => e.toJson()).toList(),
    };
