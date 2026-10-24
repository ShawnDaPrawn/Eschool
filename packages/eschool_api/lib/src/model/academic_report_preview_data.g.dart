// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_preview_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportPreviewData _$AcademicReportPreviewDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportPreviewData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'selection',
      'fingerprint',
      'can_prepare',
      'design',
      'cards',
    ],
  );
  final val = AcademicReportPreviewData(
    selection: $checkedConvert(
      'selection',
      (v) => AcademicReportSelection.fromJson(v as Map<String, dynamic>),
    ),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    canPrepare: $checkedConvert('can_prepare', (v) => v as bool),
    design: $checkedConvert(
      'design',
      (v) => AcademicReportDesignSnapshot.fromJson(v as Map<String, dynamic>),
    ),
    cards: $checkedConvert(
      'cards',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicReportPayload.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'canPrepare': 'can_prepare'});

Map<String, dynamic> _$AcademicReportPreviewDataToJson(
  AcademicReportPreviewData instance,
) => <String, dynamic>{
  'selection': instance.selection.toJson(),
  'fingerprint': instance.fingerprint,
  'can_prepare': instance.canPrepare,
  'design': instance.design.toJson(),
  'cards': instance.cards.map((e) => e.toJson()).toList(),
};
