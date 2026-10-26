// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_message.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceMessage _$SchoolAssistanceMessageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceMessage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'author_kind', 'text', 'language', 'sent_at'],
  );
  final val = SchoolAssistanceMessage(
    id: $checkedConvert('id', (v) => v as String),
    authorKind: $checkedConvert(
      'author_kind',
      (v) => $enumDecode(
        _$SchoolAssistanceMessageAuthorKindEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceMessageAuthorKindEnum.unknownDefaultOpenApi,
      ),
    ),
    text: $checkedConvert('text', (v) => v as String),
    language: $checkedConvert('language', (v) => v as String),
    sentAt: $checkedConvert('sent_at', (v) => DateTime.parse(v as String)),
  );
  return val;
}, fieldKeyMap: const {'authorKind': 'author_kind', 'sentAt': 'sent_at'});

Map<String, dynamic> _$SchoolAssistanceMessageToJson(
  SchoolAssistanceMessage instance,
) => <String, dynamic>{
  'id': instance.id,
  'author_kind':
      _$SchoolAssistanceMessageAuthorKindEnumEnumMap[instance.authorKind]!,
  'text': instance.text,
  'language': instance.language,
  'sent_at': instance.sentAt.toIso8601String(),
};

const _$SchoolAssistanceMessageAuthorKindEnumEnumMap = {
  SchoolAssistanceMessageAuthorKindEnum.parent: 'parent',
  SchoolAssistanceMessageAuthorKindEnum.office: 'office',
  SchoolAssistanceMessageAuthorKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
