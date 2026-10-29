// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_homework.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryHomework _$DiaryHomeworkFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryHomework', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'submitted',
          'status',
          'points',
          'total',
          'overdue',
        ],
      );
      final val = DiaryHomework(
        submitted: $checkedConvert('submitted', (v) => v as bool),
        status: $checkedConvert(
          'status',
          (v) => $enumDecodeNullable(
            _$DiaryHomeworkStatusEnumEnumMap,
            v,
            unknownValue: DiaryHomeworkStatusEnum.unknownDefaultOpenApi,
          ),
        ),
        points: $checkedConvert('points', (v) => (v as num?)?.toInt()),
        total: $checkedConvert('total', (v) => (v as num?)?.toInt()),
        overdue: $checkedConvert('overdue', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$DiaryHomeworkToJson(DiaryHomework instance) =>
    <String, dynamic>{
      'submitted': instance.submitted,
      'status': _$DiaryHomeworkStatusEnumEnumMap[instance.status],
      'points': instance.points,
      'total': instance.total,
      'overdue': instance.overdue,
    };

const _$DiaryHomeworkStatusEnumEnumMap = {
  DiaryHomeworkStatusEnum.inReview: 'in_review',
  DiaryHomeworkStatusEnum.accepted: 'accepted',
  DiaryHomeworkStatusEnum.rejected: 'rejected',
  DiaryHomeworkStatusEnum.resubmitted: 'resubmitted',
  DiaryHomeworkStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
