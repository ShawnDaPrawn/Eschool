// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_detail_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportDetailCard _$AcademicReportDetailCardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportDetailCard',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'payload',
        'pdf_ready',
        'generated_at',
        'comments',
        'download_url',
      ],
    );
    final val = AcademicReportDetailCard(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      payload: $checkedConvert(
        'payload',
        (v) => AcademicReportPayload.fromJson(v as Map<String, dynamic>),
      ),
      pdfReady: $checkedConvert('pdf_ready', (v) => v as bool),
      generatedAt: $checkedConvert('generated_at', (v) => v as String?),
      comments: $checkedConvert(
        'comments',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportStoredComment.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      downloadUrl: $checkedConvert('download_url', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'pdfReady': 'pdf_ready',
    'generatedAt': 'generated_at',
    'downloadUrl': 'download_url',
  },
);

Map<String, dynamic> _$AcademicReportDetailCardToJson(
  AcademicReportDetailCard instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'payload': instance.payload.toJson(),
  'pdf_ready': instance.pdfReady,
  'generated_at': instance.generatedAt,
  'comments': instance.comments.map((e) => e.toJson()).toList(),
  'download_url': instance.downloadUrl,
};
