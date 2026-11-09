// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSnapshot _$StrengthsSnapshotFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsSnapshot',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['learner_id', 'first_name', 'as_of', 'sections'],
        );
        final val = StrengthsSnapshot(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          firstName: $checkedConvert('first_name', (v) => v as String),
          asOf: $checkedConvert('as_of', (v) => DateTime.parse(v as String)),
          sections: $checkedConvert(
            'sections',
            (v) => StrengthsSharedSections.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'firstName': 'first_name',
        'asOf': 'as_of',
      },
    );

Map<String, dynamic> _$StrengthsSnapshotToJson(StrengthsSnapshot instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'first_name': instance.firstName,
      'as_of': instance.asOf.toIso8601String(),
      'sections': instance.sections.toJson(),
    };
