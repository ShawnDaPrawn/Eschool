// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_mark.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestMark _$WeeklyDigestMarkFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WeeklyDigestMark',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'release_id',
            'subject',
            'percentage_bp',
            'provisional',
          ],
        );
        final val = WeeklyDigestMark(
          releaseId: $checkedConvert('release_id', (v) => (v as num).toInt()),
          subject: $checkedConvert('subject', (v) => v as String),
          percentageBp: $checkedConvert(
            'percentage_bp',
            (v) => (v as num?)?.toInt(),
          ),
          provisional: $checkedConvert('provisional', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'releaseId': 'release_id',
        'percentageBp': 'percentage_bp',
      },
    );

Map<String, dynamic> _$WeeklyDigestMarkToJson(WeeklyDigestMark instance) =>
    <String, dynamic>{
      'release_id': instance.releaseId,
      'subject': instance.subject,
      'percentage_bp': instance.percentageBp,
      'provisional': instance.provisional,
    };
