// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_capture_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCaptureData _$AcademicCaptureDataFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicCaptureData', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['marks', 'recompute_pending']);
      final val = AcademicCaptureData(
        marks: $checkedConvert(
          'marks',
          (v) => (v as List<dynamic>)
              .map((e) => AcademicMark.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        recomputePending: $checkedConvert(
          'recompute_pending',
          (v) => v as bool,
        ),
      );
      return val;
    }, fieldKeyMap: const {'recomputePending': 'recompute_pending'});

Map<String, dynamic> _$AcademicCaptureDataToJson(
  AcademicCaptureData instance,
) => <String, dynamic>{
  'marks': instance.marks.map((e) => e.toJson()).toList(),
  'recompute_pending': instance.recomputePending,
};
