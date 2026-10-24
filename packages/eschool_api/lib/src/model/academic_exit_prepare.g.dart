// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_prepare.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitPrepare _$AcademicExitPrepareFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicExitPrepare',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'transition_id',
            'report_ids',
            'document_ids',
            'operation_id',
            'fingerprint',
          ],
        );
        final val = AcademicExitPrepare(
          transitionId: $checkedConvert(
            'transition_id',
            (v) => (v as num).toInt(),
          ),
          reportIds: $checkedConvert(
            'report_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
          documentIds: $checkedConvert(
            'document_ids',
            (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
          ),
          operationId: $checkedConvert('operation_id', (v) => v as String),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'transitionId': 'transition_id',
        'reportIds': 'report_ids',
        'documentIds': 'document_ids',
        'operationId': 'operation_id',
      },
    );

Map<String, dynamic> _$AcademicExitPrepareToJson(
  AcademicExitPrepare instance,
) => <String, dynamic>{
  'transition_id': instance.transitionId,
  'report_ids': instance.reportIds.toList(),
  'document_ids': instance.documentIds.toList(),
  'operation_id': instance.operationId,
  'fingerprint': instance.fingerprint,
};
