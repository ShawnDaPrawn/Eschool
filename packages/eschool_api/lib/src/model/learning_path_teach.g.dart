// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_teach.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathTeach _$LearningPathTeachFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearningPathTeach', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['lessons', 'tutor']);
      final val = LearningPathTeach(
        lessons: $checkedConvert(
          'lessons',
          (v) => (v as List<dynamic>)
              .map(
                (e) => LearningPathLesson.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        tutor: $checkedConvert(
          'tutor',
          (v) => LearningPathTeachTutor.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$LearningPathTeachToJson(LearningPathTeach instance) =>
    <String, dynamic>{
      'lessons': instance.lessons.map((e) => e.toJson()).toList(),
      'tutor': instance.tutor.toJson(),
    };
