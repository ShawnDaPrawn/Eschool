// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticGroup _$DiagnosticGroupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'DiagnosticGroup',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'group_id',
            'group_name',
            'subject_name',
            'available',
            'title',
            'topics',
            'learners',
          ],
        );
        final val = DiagnosticGroup(
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          groupName: $checkedConvert('group_name', (v) => v as String),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          available: $checkedConvert('available', (v) => v as bool),
          title: $checkedConvert('title', (v) => v as String?),
          topics: $checkedConvert(
            'topics',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => DiagnosticGroupTopicsInner.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
          learners: $checkedConvert(
            'learners',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => DiagnosticGroupLearnersInner.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'groupId': 'group_id',
        'groupName': 'group_name',
        'subjectName': 'subject_name',
      },
    );

Map<String, dynamic> _$DiagnosticGroupToJson(DiagnosticGroup instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'group_name': instance.groupName,
      'subject_name': instance.subjectName,
      'available': instance.available,
      'title': instance.title,
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
