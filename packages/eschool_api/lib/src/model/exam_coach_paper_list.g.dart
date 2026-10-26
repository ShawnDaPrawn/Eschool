// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperList _$ExamCoachPaperListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachPaperList', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['group_id', 'papers', 'page', 'has_more'],
      );
      final val = ExamCoachPaperList(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        papers: $checkedConvert(
          'papers',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    ExamCoachPaperSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        hasMore: $checkedConvert('has_more', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id', 'hasMore': 'has_more'});

Map<String, dynamic> _$ExamCoachPaperListToJson(ExamCoachPaperList instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'papers': instance.papers.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'has_more': instance.hasMore,
    };
