// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_explored_subject.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsExploredSubject _$StrengthsExploredSubjectFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsExploredSubject', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['subject_id', 'subject', 'topics']);
  final val = StrengthsExploredSubject(
    subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
    subject: $checkedConvert('subject', (v) => v as String),
    topics: $checkedConvert(
      'topics',
      (v) => (v as List<dynamic>)
          .map(
            (e) => StrengthsExploredTopic.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'subjectId': 'subject_id'});

Map<String, dynamic> _$StrengthsExploredSubjectToJson(
  StrengthsExploredSubject instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'subject': instance.subject,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
};
