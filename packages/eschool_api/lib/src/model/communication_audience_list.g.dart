// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceList _$CommunicationAudienceListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudienceList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = CommunicationAudienceList(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => CommunicationAudience.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationAudienceListToJson(
  CommunicationAudienceList instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
