// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_report.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferReport _$TransferReportFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TransferReport',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'sha256',
            'year_name',
            'term_name',
            'released_at',
          ],
        );
        final val = TransferReport(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          sha256: $checkedConvert('sha256', (v) => v as String),
          yearName: $checkedConvert('year_name', (v) => v as String),
          termName: $checkedConvert('term_name', (v) => v as String),
          releasedAt: $checkedConvert(
            'released_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'yearName': 'year_name',
        'termName': 'term_name',
        'releasedAt': 'released_at',
      },
    );

Map<String, dynamic> _$TransferReportToJson(TransferReport instance) =>
    <String, dynamic>{
      'id': instance.id,
      'sha256': instance.sha256,
      'year_name': instance.yearName,
      'term_name': instance.termName,
      'released_at': instance.releasedAt.toIso8601String(),
    };
