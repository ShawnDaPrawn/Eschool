// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_paper.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanPaper _$ExamCoachPlanPaperFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ExamCoachPlanPaper',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'version_id',
            'title',
            'paper_year',
            'paper_code',
          ],
        );
        final val = ExamCoachPlanPaper(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          versionId: $checkedConvert('version_id', (v) => v as String),
          title: $checkedConvert('title', (v) => v as String),
          paperYear: $checkedConvert('paper_year', (v) => (v as num).toInt()),
          paperCode: $checkedConvert('paper_code', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'versionId': 'version_id',
        'paperYear': 'paper_year',
        'paperCode': 'paper_code',
      },
    );

Map<String, dynamic> _$ExamCoachPlanPaperToJson(ExamCoachPlanPaper instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version_id': instance.versionId,
      'title': instance.title,
      'paper_year': instance.paperYear,
      'paper_code': instance.paperCode,
    };
