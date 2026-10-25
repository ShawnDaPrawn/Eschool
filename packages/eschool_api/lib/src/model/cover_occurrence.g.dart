// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_occurrence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverOccurrence _$CoverOccurrenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CoverOccurrence',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'absence_id',
            'revision',
            'state',
            'on_date',
            'source',
            'substitute_teacher_id',
            'substitute_name',
          ],
        );
        final val = CoverOccurrence(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          absenceId: $checkedConvert('absence_id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$CoverOccurrenceStateEnumEnumMap,
              v,
              unknownValue: CoverOccurrenceStateEnum.unknownDefaultOpenApi,
            ),
          ),
          onDate: $checkedConvert('on_date', (v) => v as String),
          source_: $checkedConvert(
            'source',
            (v) => CoverSource.fromJson(v as Map<String, dynamic>),
          ),
          substituteTeacherId: $checkedConvert(
            'substitute_teacher_id',
            (v) => (v as num?)?.toInt(),
          ),
          substituteName: $checkedConvert(
            'substitute_name',
            (v) => v as String?,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'absenceId': 'absence_id',
        'onDate': 'on_date',
        'source_': 'source',
        'substituteTeacherId': 'substitute_teacher_id',
        'substituteName': 'substitute_name',
      },
    );

Map<String, dynamic> _$CoverOccurrenceToJson(CoverOccurrence instance) =>
    <String, dynamic>{
      'id': instance.id,
      'absence_id': instance.absenceId,
      'revision': instance.revision,
      'state': _$CoverOccurrenceStateEnumEnumMap[instance.state]!,
      'on_date': instance.onDate,
      'source': instance.source_.toJson(),
      'substitute_teacher_id': instance.substituteTeacherId,
      'substitute_name': instance.substituteName,
    };

const _$CoverOccurrenceStateEnumEnumMap = {
  CoverOccurrenceStateEnum.open: 'open',
  CoverOccurrenceStateEnum.assigned: 'assigned',
  CoverOccurrenceStateEnum.cancelled: 'cancelled',
  CoverOccurrenceStateEnum.needsReview: 'needs_review',
  CoverOccurrenceStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
