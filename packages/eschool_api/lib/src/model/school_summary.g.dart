// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolSummary _$SchoolSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SchoolSummary',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['id', 'name', 'short_name', 'crest_url'],
        );
        final val = SchoolSummary(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          shortName: $checkedConvert('short_name', (v) => v as String?),
          crestUrl: $checkedConvert('crest_url', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {'shortName': 'short_name', 'crestUrl': 'crest_url'},
    );

Map<String, dynamic> _$SchoolSummaryToJson(SchoolSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'short_name': instance.shortName,
      'crest_url': instance.crestUrl,
    };
