// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_smart.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceSmart _$CommunicationAudienceSmartFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudienceSmart', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['match', 'parents', 'rules']);
  final val = CommunicationAudienceSmart(
    match: $checkedConvert(
      'match',
      (v) => $enumDecode(
        _$CommunicationAudienceSmartMatchEnumEnumMap,
        v,
        unknownValue: CommunicationAudienceSmartMatchEnum.unknownDefaultOpenApi,
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
});

Map<String, dynamic> _$CommunicationAudienceSmartToJson(
  CommunicationAudienceSmart instance,
) => <String, dynamic>{
  'match': _$CommunicationAudienceSmartMatchEnumEnumMap[instance.match]!,
  'parents': instance.parents,
  'rules': instance.rules.map((e) => e.toJson()).toList(),
};

const _$CommunicationAudienceSmartMatchEnumEnumMap = {
  CommunicationAudienceSmartMatchEnum.any: 'any',
  CommunicationAudienceSmartMatchEnum.all: 'all',
  CommunicationAudienceSmartMatchEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
