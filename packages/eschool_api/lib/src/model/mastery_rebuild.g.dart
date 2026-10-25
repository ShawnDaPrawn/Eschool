// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_rebuild.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryRebuild _$MasteryRebuildFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MasteryRebuild', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['group_id', 'queued']);
      final val = MasteryRebuild(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        queued: $checkedConvert('queued', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id'});

Map<String, dynamic> _$MasteryRebuildToJson(MasteryRebuild instance) =>
    <String, dynamic>{'group_id': instance.groupId, 'queued': instance.queued};
