// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_selection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitSelection _$AcademicExitSelectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitSelection',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['transition_id', 'report_ids', 'document_ids'],
    );
    final val = AcademicExitSelection(
      transitionId: $checkedConvert('transition_id', (v) => (v as num).toInt()),
      reportIds: $checkedConvert(
        'report_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
      ),
      documentIds: $checkedConvert(
        'document_ids',
        (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'transitionId': 'transition_id',
    'reportIds': 'report_ids',
    'documentIds': 'document_ids',
  },
);

Map<String, dynamic> _$AcademicExitSelectionToJson(
  AcademicExitSelection instance,
) => <String, dynamic>{
  'transition_id': instance.transitionId,
  'report_ids': instance.reportIds.toList(),
  'document_ids': instance.documentIds.toList(),
};
