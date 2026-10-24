// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_mark_capabilities.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMarkCapabilities _$AcademicMarkCapabilitiesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicMarkCapabilities', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['capture', 'correct', 'moderate', 'release'],
  );
  final val = AcademicMarkCapabilities(
    capture: $checkedConvert('capture', (v) => v as bool),
    correct: $checkedConvert('correct', (v) => v as bool),
    moderate: $checkedConvert('moderate', (v) => v as bool),
    release: $checkedConvert('release', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$AcademicMarkCapabilitiesToJson(
  AcademicMarkCapabilities instance,
) => <String, dynamic>{
  'capture': instance.capture,
  'correct': instance.correct,
  'moderate': instance.moderate,
  'release': instance.release,
};
