// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCard _$AcademicReportCardFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicReportCard',
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
          ],
        );
        final val = AcademicReportCard(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          payload: $checkedConvert(
            'payload',
            (v) => AcademicReportPayload.fromJson(v as Map<String, dynamic>),
          ),
          pdfReady: $checkedConvert('pdf_ready', (v) => v as bool),
          generatedAt: $checkedConvert('generated_at', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'pdfReady': 'pdf_ready',
        'generatedAt': 'generated_at',
      },
    );

Map<String, dynamic> _$AcademicReportCardToJson(AcademicReportCard instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'payload': instance.payload.toJson(),
      'pdf_ready': instance.pdfReady,
      'generated_at': instance.generatedAt,
    };
