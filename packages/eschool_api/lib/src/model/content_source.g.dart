// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_source.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSource _$ContentSourceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentSource',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'name',
            'extension',
            'mime',
            'bytes',
            'scan_state',
            'scanned_at',
          ],
        );
        final val = ContentSource(
          name: $checkedConvert('name', (v) => v as String),
          extension_: $checkedConvert('extension', (v) => v as String),
          mime: $checkedConvert('mime', (v) => v as String),
          bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
          scanState: $checkedConvert(
            'scan_state',
            (v) => $enumDecode(
              _$ContentSourceScanStateEnumEnumMap,
              v,
              unknownValue: ContentSourceScanStateEnum.unknownDefaultOpenApi,
            ),
          ),
          scannedAt: $checkedConvert(
            'scanned_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'extension_': 'extension',
        'scanState': 'scan_state',
        'scannedAt': 'scanned_at',
      },
    );

Map<String, dynamic> _$ContentSourceToJson(ContentSource instance) =>
    <String, dynamic>{
      'name': instance.name,
      'extension': instance.extension_,
      'mime': instance.mime,
      'bytes': instance.bytes,
      'scan_state': _$ContentSourceScanStateEnumEnumMap[instance.scanState]!,
      'scanned_at': instance.scannedAt?.toIso8601String(),
    };

const _$ContentSourceScanStateEnumEnumMap = {
  ContentSourceScanStateEnum.pending: 'pending',
  ContentSourceScanStateEnum.clean: 'clean',
  ContentSourceScanStateEnum.infected: 'infected',
  ContentSourceScanStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
