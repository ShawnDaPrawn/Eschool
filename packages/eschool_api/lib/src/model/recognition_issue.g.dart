// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_issue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionIssue _$RecognitionIssueFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionIssue',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'learner_id',
            'session_year_id',
            'point_category_id',
            'points',
            'reason',
            'public',
          ],
        );
        final val = RecognitionIssue(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          pointCategoryId: $checkedConvert(
            'point_category_id',
            (v) => (v as num).toInt(),
          ),
          points: $checkedConvert('points', (v) => (v as num).toInt()),
          reason: $checkedConvert('reason', (v) => v as String),
          public: $checkedConvert('public', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
        'sessionYearId': 'session_year_id',
        'pointCategoryId': 'point_category_id',
      },
    );

Map<String, dynamic> _$RecognitionIssueToJson(RecognitionIssue instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'learner_id': instance.learnerId,
      'session_year_id': instance.sessionYearId,
      'point_category_id': instance.pointCategoryId,
      'points': instance.points,
      'reason': instance.reason,
      'public': instance.public,
    };
