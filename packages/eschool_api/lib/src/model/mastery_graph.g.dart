// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_graph.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryGraph _$MasteryGraphFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MasteryGraph', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['subject_id', 'settings', 'nodes', 'links'],
      );
      final val = MasteryGraph(
        subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
        settings: $checkedConvert(
          'settings',
          (v) => MasterySettings.fromJson(v as Map<String, dynamic>),
        ),
        nodes: $checkedConvert(
          'nodes',
          (v) => (v as List<dynamic>)
              .map((e) => MasteryNode.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        links: $checkedConvert(
          'links',
          (v) => (v as List<dynamic>)
              .map((e) => MasteryLink.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'subjectId': 'subject_id'});

Map<String, dynamic> _$MasteryGraphToJson(MasteryGraph instance) =>
    <String, dynamic>{
      'subject_id': instance.subjectId,
      'settings': instance.settings.toJson(),
      'nodes': instance.nodes.map((e) => e.toJson()).toList(),
      'links': instance.links.map((e) => e.toJson()).toList(),
    };
