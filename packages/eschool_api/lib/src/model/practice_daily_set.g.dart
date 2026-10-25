// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_daily_set.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeDailySet _$PracticeDailySetFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeDailySet',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'school_id',
            'reader_id',
            'learner_id',
            'group_id',
            'practice_on',
            'server_time',
            'offline_until',
            'estimated_seconds',
            'completed',
            'items',
            'streak_days',
          ],
        );
        final val = PracticeDailySet(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          practiceOn: $checkedConvert('practice_on', (v) => v as String),
          serverTime: $checkedConvert(
            'server_time',
            (v) => DateTime.parse(v as String),
          ),
          offlineUntil: $checkedConvert(
            'offline_until',
            (v) => DateTime.parse(v as String),
          ),
          estimatedSeconds: $checkedConvert(
            'estimated_seconds',
            (v) => (v as num).toInt(),
          ),
          completed: $checkedConvert('completed', (v) => v as bool),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => PracticeSetEntry.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          streakDays: $checkedConvert('streak_days', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'learnerId': 'learner_id',
        'groupId': 'group_id',
        'practiceOn': 'practice_on',
        'serverTime': 'server_time',
        'offlineUntil': 'offline_until',
        'estimatedSeconds': 'estimated_seconds',
        'streakDays': 'streak_days',
      },
    );

Map<String, dynamic> _$PracticeDailySetToJson(PracticeDailySet instance) =>
    <String, dynamic>{
      'id': instance.id,
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'group_id': instance.groupId,
      'practice_on': instance.practiceOn,
      'server_time': instance.serverTime.toIso8601String(),
      'offline_until': instance.offlineUntil.toIso8601String(),
      'estimated_seconds': instance.estimatedSeconds,
      'completed': instance.completed,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'streak_days': instance.streakDays,
    };
