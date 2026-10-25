// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestDetail _$WeeklyDigestDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WeeklyDigestDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'language',
            'week_start',
            'week_end',
            'template_version',
            'title',
            'summary',
            'evidence',
            'home_action',
            'expires_at',
          ],
        );
        final val = WeeklyDigestDetail(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          language: $checkedConvert('language', (v) => v as String),
          weekStart: $checkedConvert('week_start', (v) => v as String),
          weekEnd: $checkedConvert('week_end', (v) => v as String),
          templateVersion: $checkedConvert(
            'template_version',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
          summary: $checkedConvert('summary', (v) => v as String),
          evidence: $checkedConvert(
            'evidence',
            (v) => WeeklyDigestEvidence.fromJson(v as Map<String, dynamic>),
          ),
          homeAction: $checkedConvert(
            'home_action',
            (v) => WeeklyDigestHomeAction.fromJson(v as Map<String, dynamic>),
          ),
          expiresAt: $checkedConvert(
            'expires_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'weekStart': 'week_start',
        'weekEnd': 'week_end',
        'templateVersion': 'template_version',
        'homeAction': 'home_action',
        'expiresAt': 'expires_at',
      },
    );

Map<String, dynamic> _$WeeklyDigestDetailToJson(WeeklyDigestDetail instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'language': instance.language,
      'week_start': instance.weekStart,
      'week_end': instance.weekEnd,
      'template_version': instance.templateVersion,
      'title': instance.title,
      'summary': instance.summary,
      'evidence': instance.evidence.toJson(),
      'home_action': instance.homeAction.toJson(),
      'expires_at': instance.expiresAt.toIso8601String(),
    };
