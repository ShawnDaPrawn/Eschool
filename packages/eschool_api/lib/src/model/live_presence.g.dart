// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_presence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LivePresence _$LivePresenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LivePresence',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'type',
            'school_id',
            'group_id',
            'revision',
            'online_count',
          ],
        );
        final val = LivePresence(
          type: $checkedConvert('type', (v) => v as String),
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          onlineCount: $checkedConvert(
            'online_count',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'groupId': 'group_id',
        'onlineCount': 'online_count',
      },
    );

Map<String, dynamic> _$LivePresenceToJson(LivePresence instance) =>
    <String, dynamic>{
      'type': instance.type,
      'school_id': instance.schoolId,
      'group_id': instance.groupId,
      'revision': instance.revision,
      'online_count': instance.onlineCount,
    };
