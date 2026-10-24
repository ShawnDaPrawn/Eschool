// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_batch_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportBatchData _$AcademicReportBatchDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportBatchData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'state',
        'revision',
        'fingerprint',
        'source_fingerprint',
        'selection',
        'design',
        'signatures',
        'released_at',
        'cards',
      ],
    );
    final val = AcademicReportBatchData(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      state: $checkedConvert('state', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      sourceFingerprint: $checkedConvert(
        'source_fingerprint',
        (v) => v as String,
      ),
      selection: $checkedConvert(
        'selection',
        (v) => AcademicReportSelection.fromJson(v as Map<String, dynamic>),
      ),
      design: $checkedConvert(
        'design',
        (v) => AcademicReportDesignSnapshot.fromJson(v as Map<String, dynamic>),
      ),
      signatures: $checkedConvert(
        'signatures',
        (v) => AcademicReportSignatures.fromJson(v as Map<String, dynamic>),
      ),
      releasedAt: $checkedConvert('released_at', (v) => v as String?),
      cards: $checkedConvert(
        'cards',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicReportCard.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sourceFingerprint': 'source_fingerprint',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$AcademicReportBatchDataToJson(
  AcademicReportBatchData instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': instance.state,
  'revision': instance.revision,
  'fingerprint': instance.fingerprint,
  'source_fingerprint': instance.sourceFingerprint,
  'selection': instance.selection.toJson(),
  'design': instance.design.toJson(),
  'signatures': instance.signatures.toJson(),
  'released_at': instance.releasedAt,
  'cards': instance.cards.map((e) => e.toJson()).toList(),
};
