// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionLearner _$RecognitionLearnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionLearner',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'grade_level_id',
            'session_year_id',
          ],
        );
        final val = RecognitionLearner(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'gradeLevelId': 'grade_level_id',
        'sessionYearId': 'session_year_id',
      },
    );

Map<String, dynamic> _$RecognitionLearnerToJson(RecognitionLearner instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
    };
