// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_subject_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasterySubjectList _$MasterySubjectListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MasterySubjectList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['learner_id', 'subjects']);
      final val = MasterySubjectList(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        subjects: $checkedConvert(
          'subjects',
          (v) => (v as List<dynamic>)
              .map(
                (e) => MasterySubjectChoice.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$MasterySubjectListToJson(MasterySubjectList instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'subjects': instance.subjects.map((e) => e.toJson()).toList(),
    };
