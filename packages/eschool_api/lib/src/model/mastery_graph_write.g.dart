// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_graph_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryGraphWrite _$MasteryGraphWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MasteryGraphWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'expected_revision',
            'family_marks_enabled',
            'links',
          ],
        );
        final val = MasteryGraphWrite(
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          familyMarksEnabled: $checkedConvert(
            'family_marks_enabled',
            (v) => v as bool,
          ),
          links: $checkedConvert(
            'links',
            (v) => (v as List<dynamic>)
                .map((e) => MasteryLink.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'expectedRevision': 'expected_revision',
        'familyMarksEnabled': 'family_marks_enabled',
      },
    );

Map<String, dynamic> _$MasteryGraphWriteToJson(MasteryGraphWrite instance) =>
    <String, dynamic>{
      'expected_revision': instance.expectedRevision,
      'family_marks_enabled': instance.familyMarksEnabled,
      'links': instance.links.map((e) => e.toJson()).toList(),
    };
