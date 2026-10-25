// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_candidates.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverCandidates _$CoverCandidatesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CoverCandidates', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['occurrence_id', 'revision', 'teachers'],
      );
      final val = CoverCandidates(
        occurrenceId: $checkedConvert(
          'occurrence_id',
          (v) => (v as num).toInt(),
        ),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        teachers: $checkedConvert(
          'teachers',
          (v) => (v as List<dynamic>)
              .map((e) => CoverTeacher.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'occurrenceId': 'occurrence_id'});

Map<String, dynamic> _$CoverCandidatesToJson(CoverCandidates instance) =>
    <String, dynamic>{
      'occurrence_id': instance.occurrenceId,
      'revision': instance.revision,
      'teachers': instance.teachers.map((e) => e.toJson()).toList(),
    };
