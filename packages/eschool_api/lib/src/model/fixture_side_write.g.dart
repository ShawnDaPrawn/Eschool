// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_side_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSideWrite _$FixtureSideWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureSideWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'team_label',
            'sheet_shared',
            'selections',
          ],
        );
        final val = FixtureSideWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num?)?.toInt(),
          ),
          teamLabel: $checkedConvert('team_label', (v) => v as String),
          sheetShared: $checkedConvert('sheet_shared', (v) => v as bool),
          notes: $checkedConvert('notes', (v) => v as String?),
          selections: $checkedConvert(
            'selections',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => FixtureSideWriteSelectionsInner.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'activityGroupId': 'activity_group_id',
        'teamLabel': 'team_label',
        'sheetShared': 'sheet_shared',
      },
    );

Map<String, dynamic> _$FixtureSideWriteToJson(FixtureSideWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'activity_group_id': ?instance.activityGroupId,
      'team_label': instance.teamLabel,
      'sheet_shared': instance.sheetShared,
      'notes': ?instance.notes,
      'selections': instance.selections.map((e) => e.toJson()).toList(),
    };
