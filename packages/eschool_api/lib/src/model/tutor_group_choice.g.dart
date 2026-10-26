// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorGroupChoice _$TutorGroupChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TutorGroupChoice',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'subject_id',
        'subject_name',
        'sources',
      ],
    );
    final val = TutorGroupChoice(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      subjectName: $checkedConvert('subject_name', (v) => v as String?),
      sources: $checkedConvert(
        'sources',
        (v) => (v as List<dynamic>)
            .map((e) => TutorSourceChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>?)
            ?.map((e) => TutorTopicChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'subjectId': 'subject_id', 'subjectName': 'subject_name'},
);

Map<String, dynamic> _$TutorGroupChoiceToJson(TutorGroupChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'sources': instance.sources.map((e) => e.toJson()).toList(),
      'topics': ?instance.topics?.map((e) => e.toJson()).toList(),
    };
