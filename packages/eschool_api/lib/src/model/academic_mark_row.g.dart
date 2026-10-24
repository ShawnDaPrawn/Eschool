// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_mark_row.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMarkRow _$AcademicMarkRowFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicMarkRow',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'learner_id',
            'expected_revision',
            'disposition',
            'mark',
            'question_marks',
          ],
        );
        final val = AcademicMarkRow(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          disposition: $checkedConvert(
            'disposition',
            (v) => $enumDecode(
              _$AcademicMarkRowDispositionEnumEnumMap,
              v,
              unknownValue:
                  AcademicMarkRowDispositionEnum.unknownDefaultOpenApi,
            ),
          ),
          mark: $checkedConvert('mark', (v) => v as String?),
          note: $checkedConvert('note', (v) => v as String?),
          questionMarks: $checkedConvert(
            'question_marks',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      AcademicQuestionScore.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'expectedRevision': 'expected_revision',
        'questionMarks': 'question_marks',
      },
    );

Map<String, dynamic> _$AcademicMarkRowToJson(
  AcademicMarkRow instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'expected_revision': instance.expectedRevision,
  'disposition': _$AcademicMarkRowDispositionEnumEnumMap[instance.disposition]!,
  'mark': instance.mark,
  'note': ?instance.note,
  'question_marks': instance.questionMarks.map((e) => e.toJson()).toList(),
};

const _$AcademicMarkRowDispositionEnumEnumMap = {
  AcademicMarkRowDispositionEnum.recorded: 'recorded',
  AcademicMarkRowDispositionEnum.missing: 'missing',
  AcademicMarkRowDispositionEnum.absent: 'absent',
  AcademicMarkRowDispositionEnum.excused: 'excused',
  AcademicMarkRowDispositionEnum.catchUp: 'catch_up',
  AcademicMarkRowDispositionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
