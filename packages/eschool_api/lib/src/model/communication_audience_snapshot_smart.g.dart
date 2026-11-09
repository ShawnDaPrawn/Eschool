// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_snapshot_smart.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceSnapshotSmart _$CommunicationAudienceSnapshotSmartFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudienceSnapshotSmart', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['session_year_id', 'match', 'parents', 'rules'],
  );
  final val = CommunicationAudienceSnapshotSmart(
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    match: $checkedConvert(
      'match',
      (v) => $enumDecode(
        _$CommunicationAudienceSnapshotSmartMatchEnumEnumMap,
        v,
        unknownValue:
            CommunicationAudienceSnapshotSmartMatchEnum.unknownDefaultOpenApi,
      ),
    ),
    parents: $checkedConvert('parents', (v) => v as bool),
    rules: $checkedConvert(
      'rules',
      (v) => (v as List<dynamic>)
          .map(
            (e) => CommunicationSmartRulesInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$CommunicationAudienceSnapshotSmartToJson(
  CommunicationAudienceSnapshotSmart instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'match':
      _$CommunicationAudienceSnapshotSmartMatchEnumEnumMap[instance.match]!,
  'parents': instance.parents,
  'rules': instance.rules.map((e) => e.toJson()).toList(),
};

const _$CommunicationAudienceSnapshotSmartMatchEnumEnumMap = {
  CommunicationAudienceSnapshotSmartMatchEnum.any: 'any',
  CommunicationAudienceSnapshotSmartMatchEnum.all: 'all',
  CommunicationAudienceSnapshotSmartMatchEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
