// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_side.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSide _$FixtureSideFromJson(Map<String, dynamic> json) => $checkedCreate(
  'FixtureSide',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'activity_group_id',
        'group_name',
        'team_label',
        'sheet_shared',
        'notes',
        'revision',
        'selections',
      ],
    );
    final val = FixtureSide(
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      groupName: $checkedConvert('group_name', (v) => v as String),
      teamLabel: $checkedConvert('team_label', (v) => v as String),
      sheetShared: $checkedConvert('sheet_shared', (v) => v as bool),
      notes: $checkedConvert('notes', (v) => v as String?),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      selections: $checkedConvert(
        'selections',
        (v) => (v as List<dynamic>)
            .map(
              (e) => FixtureSideSelectionsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'groupName': 'group_name',
    'teamLabel': 'team_label',
    'sheetShared': 'sheet_shared',
  },
);

Map<String, dynamic> _$FixtureSideToJson(FixtureSide instance) =>
    <String, dynamic>{
      'activity_group_id': instance.activityGroupId,
      'group_name': instance.groupName,
      'team_label': instance.teamLabel,
      'sheet_shared': instance.sheetShared,
      'notes': instance.notes,
      'revision': instance.revision,
      'selections': instance.selections.map((e) => e.toJson()).toList(),
    };
