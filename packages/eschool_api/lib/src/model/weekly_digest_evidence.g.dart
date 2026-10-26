// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_evidence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestEvidence _$WeeklyDigestEvidenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WeeklyDigestEvidence',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'marks',
        'attendance',
        'practice',
        'events',
        'event_count',
        'action_kind',
        'content_version_id',
      ],
    );
    final val = WeeklyDigestEvidence(
      marks: $checkedConvert(
        'marks',
        (v) => (v as List<dynamic>)
            .map((e) => WeeklyDigestMark.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      attendance: $checkedConvert(
        'attendance',
        (v) => WeeklyDigestAttendance.fromJson(v as Map<String, dynamic>),
      ),
      practice: $checkedConvert(
        'practice',
        (v) => WeeklyDigestPractice.fromJson(v as Map<String, dynamic>),
      ),
      events: $checkedConvert(
        'events',
        (v) => (v as List<dynamic>)
            .map((e) => WeeklyDigestEvent.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      eventCount: $checkedConvert('event_count', (v) => (v as num).toInt()),
      actionKind: $checkedConvert(
        'action_kind',
        (v) => $enumDecode(
          _$WeeklyDigestEvidenceActionKindEnumEnumMap,
          v,
          unknownValue:
              WeeklyDigestEvidenceActionKindEnum.unknownDefaultOpenApi,
        ),
      ),
      contentVersionId: $checkedConvert(
        'content_version_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'eventCount': 'event_count',
    'actionKind': 'action_kind',
    'contentVersionId': 'content_version_id',
  },
);

Map<String, dynamic> _$WeeklyDigestEvidenceToJson(
  WeeklyDigestEvidence instance,
) => <String, dynamic>{
  'marks': instance.marks.map((e) => e.toJson()).toList(),
  'attendance': instance.attendance.toJson(),
  'practice': instance.practice.toJson(),
  'events': instance.events.map((e) => e.toJson()).toList(),
  'event_count': instance.eventCount,
  'action_kind':
      _$WeeklyDigestEvidenceActionKindEnumEnumMap[instance.actionKind]!,
  'content_version_id': instance.contentVersionId,
};

const _$WeeklyDigestEvidenceActionKindEnumEnumMap = {
  WeeklyDigestEvidenceActionKindEnum.practice: 'practice',
  WeeklyDigestEvidenceActionKindEnum.marks: 'marks',
  WeeklyDigestEvidenceActionKindEnum.attendance: 'attendance',
  WeeklyDigestEvidenceActionKindEnum.calendar: 'calendar',
  WeeklyDigestEvidenceActionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
