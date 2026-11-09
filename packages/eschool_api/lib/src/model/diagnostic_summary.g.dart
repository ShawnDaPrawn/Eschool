// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticSummary _$DiagnosticSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiagnosticSummary', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['learner_id', 'diagnostics']);
      final val = DiagnosticSummary(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        diagnostics: $checkedConvert(
          'diagnostics',
          (v) => (v as List<dynamic>)
              .map(
                (e) => DiagnosticSummaryDiagnosticsInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$DiagnosticSummaryToJson(DiagnosticSummary instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'diagnostics': instance.diagnostics.map((e) => e.toJson()).toList(),
    };
