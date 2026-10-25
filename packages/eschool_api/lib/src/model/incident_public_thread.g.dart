// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_public_thread.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPublicThread _$IncidentPublicThreadFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IncidentPublicThread', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['author', 'body', 'created_at']);
  final val = IncidentPublicThread(
    author: $checkedConvert(
      'author',
      (v) => $enumDecode(
        _$IncidentPublicThreadAuthorEnumEnumMap,
        v,
        unknownValue: IncidentPublicThreadAuthorEnum.unknownDefaultOpenApi,
      ),
    ),
    body: $checkedConvert('body', (v) => v as String),
    createdAt: $checkedConvert(
      'created_at',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'createdAt': 'created_at'});

Map<String, dynamic> _$IncidentPublicThreadToJson(
  IncidentPublicThread instance,
) => <String, dynamic>{
  'author': _$IncidentPublicThreadAuthorEnumEnumMap[instance.author]!,
  'body': instance.body,
  'created_at': instance.createdAt?.toIso8601String(),
};

const _$IncidentPublicThreadAuthorEnumEnumMap = {
  IncidentPublicThreadAuthorEnum.reporter: 'reporter',
  IncidentPublicThreadAuthorEnum.school: 'school',
  IncidentPublicThreadAuthorEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
