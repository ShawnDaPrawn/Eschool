// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_public_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPublicView _$IncidentPublicViewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'IncidentPublicView',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'anonymous',
            'body',
            'urgency',
            'status',
            'revision',
            'submitted_at',
            'emergency_guidance',
            'threads',
            'has_older',
            'next_before',
          ],
        );
        final val = IncidentPublicView(
          anonymous: $checkedConvert('anonymous', (v) => v as bool),
          body: $checkedConvert('body', (v) => v as String),
          urgency: $checkedConvert(
            'urgency',
            (v) => $enumDecode(
              _$IncidentPublicViewUrgencyEnumEnumMap,
              v,
              unknownValue: IncidentPublicViewUrgencyEnum.unknownDefaultOpenApi,
            ),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$IncidentPublicViewStatusEnumEnumMap,
              v,
              unknownValue: IncidentPublicViewStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          submittedAt: $checkedConvert(
            'submitted_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          emergencyGuidance: $checkedConvert(
            'emergency_guidance',
            (v) => v as String,
          ),
          threads: $checkedConvert(
            'threads',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      IncidentPublicThread.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          hasOlder: $checkedConvert('has_older', (v) => v as bool),
          nextBefore: $checkedConvert(
            'next_before',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'submittedAt': 'submitted_at',
        'emergencyGuidance': 'emergency_guidance',
        'hasOlder': 'has_older',
        'nextBefore': 'next_before',
      },
    );

Map<String, dynamic> _$IncidentPublicViewToJson(IncidentPublicView instance) =>
    <String, dynamic>{
      'anonymous': instance.anonymous,
      'body': instance.body,
      'urgency': _$IncidentPublicViewUrgencyEnumEnumMap[instance.urgency]!,
      'status': _$IncidentPublicViewStatusEnumEnumMap[instance.status]!,
      'revision': instance.revision,
      'submitted_at': instance.submittedAt?.toIso8601String(),
      'emergency_guidance': instance.emergencyGuidance,
      'threads': instance.threads.map((e) => e.toJson()).toList(),
      'has_older': instance.hasOlder,
      'next_before': instance.nextBefore,
    };

const _$IncidentPublicViewUrgencyEnumEnumMap = {
  IncidentPublicViewUrgencyEnum.routine: 'routine',
  IncidentPublicViewUrgencyEnum.urgent: 'urgent',
  IncidentPublicViewUrgencyEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$IncidentPublicViewStatusEnumEnumMap = {
  IncidentPublicViewStatusEnum.open: 'open',
  IncidentPublicViewStatusEnum.triaged: 'triaged',
  IncidentPublicViewStatusEnum.resolved: 'resolved',
  IncidentPublicViewStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
