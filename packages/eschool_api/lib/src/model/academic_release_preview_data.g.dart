// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_preview_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasePreviewData _$AcademicReleasePreviewDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasePreviewData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'mode',
      'provisional',
      'fingerprint',
      'can_release',
      'learners',
    ],
  );
  final val = AcademicReleasePreviewData(
    mode: $checkedConvert('mode', (v) => v as String),
    provisional: $checkedConvert('provisional', (v) => v as bool),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    canRelease: $checkedConvert('can_release', (v) => v as bool),
    learners: $checkedConvert(
      'learners',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicReleaseLearner.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'canRelease': 'can_release'});

Map<String, dynamic> _$AcademicReleasePreviewDataToJson(
  AcademicReleasePreviewData instance,
) => <String, dynamic>{
  'mode': instance.mode,
  'provisional': instance.provisional,
  'fingerprint': instance.fingerprint,
  'can_release': instance.canRelease,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
