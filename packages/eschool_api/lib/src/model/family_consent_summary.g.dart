// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentSummary _$FamilyConsentSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyConsentSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'title',
        'activity_group_id',
        'session_year_id',
        'status',
        'event_starts_at',
        'event_ends_at',
        'respond_by',
      ],
    );
    final val = FamilyConsentSummary(
      id: $checkedConvert('id', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      status: $checkedConvert('status', (v) => v as String),
      eventStartsAt: $checkedConvert(
        'event_starts_at',
        (v) => DateTime.parse(v as String),
      ),
      eventEndsAt: $checkedConvert(
        'event_ends_at',
        (v) => DateTime.parse(v as String),
      ),
      respondBy: $checkedConvert(
        'respond_by',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'sessionYearId': 'session_year_id',
    'eventStartsAt': 'event_starts_at',
    'eventEndsAt': 'event_ends_at',
    'respondBy': 'respond_by',
  },
);

Map<String, dynamic> _$FamilyConsentSummaryToJson(
  FamilyConsentSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'activity_group_id': instance.activityGroupId,
  'session_year_id': instance.sessionYearId,
  'status': instance.status,
  'event_starts_at': instance.eventStartsAt.toIso8601String(),
  'event_ends_at': instance.eventEndsAt.toIso8601String(),
  'respond_by': instance.respondBy.toIso8601String(),
};
