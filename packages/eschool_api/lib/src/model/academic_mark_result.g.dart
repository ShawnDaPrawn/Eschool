// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_mark_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMarkResult _$AcademicMarkResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicMarkResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'percentage_bp',
        'running_bp',
        'coverage_bp',
        'rating_level',
        'running_rating_level',
        'complete',
        'provisional',
        'components',
        'tasks',
        'warnings',
        'rounding',
        'absent_policy',
      ],
    );
    final val = AcademicMarkResult(
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      runningBp: $checkedConvert('running_bp', (v) => (v as num?)?.toInt()),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      runningRatingLevel: $checkedConvert(
        'running_rating_level',
        (v) => (v as num?)?.toInt(),
      ),
      complete: $checkedConvert('complete', (v) => v as bool),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      components: $checkedConvert(
        'components',
        (v) => (v as Map<String, dynamic>).map(
          (k, e) => MapEntry(
            k,
            AcademicComponentResult.fromJson(e as Map<String, dynamic>),
          ),
        ),
      ),
      tasks: $checkedConvert(
        'tasks',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicTaskResult.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      warnings: $checkedConvert(
        'warnings',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      rounding: $checkedConvert('rounding', (v) => v as String),
      absentPolicy: $checkedConvert('absent_policy', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'percentageBp': 'percentage_bp',
    'runningBp': 'running_bp',
    'coverageBp': 'coverage_bp',
    'ratingLevel': 'rating_level',
    'runningRatingLevel': 'running_rating_level',
    'absentPolicy': 'absent_policy',
  },
);

Map<String, dynamic> _$AcademicMarkResultToJson(AcademicMarkResult instance) =>
    <String, dynamic>{
      'percentage_bp': instance.percentageBp,
      'running_bp': instance.runningBp,
      'coverage_bp': instance.coverageBp,
      'rating_level': instance.ratingLevel,
      'running_rating_level': instance.runningRatingLevel,
      'complete': instance.complete,
      'provisional': instance.provisional,
      'components': instance.components.map((k, e) => MapEntry(k, e.toJson())),
      'tasks': instance.tasks.map((e) => e.toJson()).toList(),
      'warnings': instance.warnings,
      'rounding': instance.rounding,
      'absent_policy': instance.absentPolicy,
    };
