// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_school.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportSchool _$LearnerPassportSchoolFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerPassportSchool',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'name',
        'current',
        'attended_from',
        'attended_to',
        'awards',
        'activities',
        'service',
      ],
    );
    final val = LearnerPassportSchool(
      name: $checkedConvert('name', (v) => v as String),
      current: $checkedConvert('current', (v) => v as bool),
      attendedFrom: $checkedConvert(
        'attended_from',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      attendedTo: $checkedConvert(
        'attended_to',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      awards: $checkedConvert(
        'awards',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearnerPassportAward.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      activities: $checkedConvert(
        'activities',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  LearnerPassportActivity.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      service: $checkedConvert(
        'service',
        (v) => v == null
            ? null
            : LearnerPassportService.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'attendedFrom': 'attended_from',
    'attendedTo': 'attended_to',
  },
);

Map<String, dynamic> _$LearnerPassportSchoolToJson(
  LearnerPassportSchool instance,
) => <String, dynamic>{
  'name': instance.name,
  'current': instance.current,
  'attended_from': instance.attendedFrom?.toIso8601String(),
  'attended_to': instance.attendedTo?.toIso8601String(),
  'awards': instance.awards.map((e) => e.toJson()).toList(),
  'activities': instance.activities.map((e) => e.toJson()).toList(),
  'service': instance.service?.toJson(),
};
