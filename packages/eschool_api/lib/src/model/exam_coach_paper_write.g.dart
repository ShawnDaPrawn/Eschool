// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperWrite _$ExamCoachPaperWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ExamCoachPaperWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'expected_revision',
            'title',
            'framework',
            'paper_year',
            'paper_code',
            'group_ids',
            'licence',
            'questions',
          ],
        );
        final val = ExamCoachPaperWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
          framework: $checkedConvert('framework', (v) => v as String),
          paperYear: $checkedConvert('paper_year', (v) => (v as num).toInt()),
          paperCode: $checkedConvert('paper_code', (v) => v as String),
          groupIds: $checkedConvert(
            'group_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          licence: $checkedConvert(
            'licence',
            (v) => ExamCoachLicence.fromJson(v as Map<String, dynamic>),
          ),
          questions: $checkedConvert(
            'questions',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => ExamCoachQuestionWrite.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'expectedRevision': 'expected_revision',
        'paperYear': 'paper_year',
        'paperCode': 'paper_code',
        'groupIds': 'group_ids',
      },
    );

Map<String, dynamic> _$ExamCoachPaperWriteToJson(
  ExamCoachPaperWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'title': instance.title,
  'framework': instance.framework,
  'paper_year': instance.paperYear,
  'paper_code': instance.paperCode,
  'group_ids': instance.groupIds,
  'licence': instance.licence.toJson(),
  'questions': instance.questions.map((e) => e.toJson()).toList(),
};
