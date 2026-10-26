// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_staff_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachStaffChoices _$ExamCoachStaffChoicesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachStaffChoices',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'group_id',
        'frameworks',
        'topics',
        'audience_groups',
        'writable',
      ],
    );
    final val = ExamCoachStaffChoices(
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      frameworks: $checkedConvert(
        'frameworks',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ExamCoachTopicChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      audienceGroups: $checkedConvert(
        'audience_groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ExamCoachGroupChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      writable: $checkedConvert('writable', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'audienceGroups': 'audience_groups',
  },
);

Map<String, dynamic> _$ExamCoachStaffChoicesToJson(
  ExamCoachStaffChoices instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'frameworks': instance.frameworks,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'audience_groups': instance.audienceGroups.map((e) => e.toJson()).toList(),
  'writable': instance.writable,
};
