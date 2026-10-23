// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReference _$AcademicReferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['grades', 'rating_scale']);
  final val = AcademicReference(
    grades: $checkedConvert(
      'grades',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicGradeLevel.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    ratingScale: $checkedConvert(
      'rating_scale',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicRatingLevel.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'ratingScale': 'rating_scale'});

Map<String, dynamic> _$AcademicReferenceToJson(AcademicReference instance) =>
    <String, dynamic>{
      'grades': instance.grades.map((e) => e.toJson()).toList(),
      'rating_scale': instance.ratingScale.map((e) => e.toJson()).toList(),
    };
