// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'approval_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ApprovalLearner _$ApprovalLearnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ApprovalLearner', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'image', 'student_number', 'school'],
      );
      final val = ApprovalLearner(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        image: $checkedConvert('image', (v) => v as String?),
        studentNumber: $checkedConvert('student_number', (v) => v as String?),
        school: $checkedConvert(
          'school',
          (v) => SchoolSummary.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'studentNumber': 'student_number'});

Map<String, dynamic> _$ApprovalLearnerToJson(ApprovalLearner instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'image': instance.image,
      'student_number': instance.studentNumber,
      'school': instance.school.toJson(),
    };
