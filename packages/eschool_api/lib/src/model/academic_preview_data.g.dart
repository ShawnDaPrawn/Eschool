// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_preview_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicPreviewData _$AcademicPreviewDataFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicPreviewData', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['previews']);
      final val = AcademicPreviewData(
        previews: $checkedConvert(
          'previews',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    AcademicLearnerPreview.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$AcademicPreviewDataToJson(
  AcademicPreviewData instance,
) => <String, dynamic>{
  'previews': instance.previews.map((e) => e.toJson()).toList(),
};
