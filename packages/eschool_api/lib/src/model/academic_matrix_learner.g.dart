// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_matrix_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMatrixLearner _$AcademicMatrixLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicMatrixLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'student_number',
        'display_name',
        'marks',
        'result',
      ],
    );
    final val = AcademicMatrixLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      studentNumber: $checkedConvert('student_number', (v) => v as String?),
      displayName: $checkedConvert('display_name', (v) => v as String),
      marks: $checkedConvert(
        'marks',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicMark.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      result: $checkedConvert(
        'result',
        (v) => AcademicMarkResult.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'studentNumber': 'student_number',
    'displayName': 'display_name',
  },
);

Map<String, dynamic> _$AcademicMatrixLearnerToJson(
  AcademicMatrixLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'student_number': instance.studentNumber,
  'display_name': instance.displayName,
  'marks': instance.marks.map((e) => e.toJson()).toList(),
  'result': instance.result.toJson(),
};
