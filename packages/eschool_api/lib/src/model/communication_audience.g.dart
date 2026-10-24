// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudience _$CommunicationAudienceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudience', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'name',
      'revision',
      'active',
      'editable',
      'user_ids',
    ],
  );
  final val = CommunicationAudience(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    active: $checkedConvert('active', (v) => v as bool),
    editable: $checkedConvert('editable', (v) => v as bool),
    userIds: $checkedConvert(
      'user_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
    ),
  );
  return val;
}, fieldKeyMap: const {'userIds': 'user_ids'});

Map<String, dynamic> _$CommunicationAudienceToJson(
  CommunicationAudience instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'revision': instance.revision,
  'active': instance.active,
  'editable': instance.editable,
  'user_ids': instance.userIds.toList(),
};
