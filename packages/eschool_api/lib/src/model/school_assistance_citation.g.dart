// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_citation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceCitation _$SchoolAssistanceCitationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceCitation', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['token', 'kind', 'title', 'locator']);
  final val = SchoolAssistanceCitation(
    token: $checkedConvert('token', (v) => v as String),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$SchoolAssistanceCitationKindEnumEnumMap,
        v,
        unknownValue: SchoolAssistanceCitationKindEnum.unknownDefaultOpenApi,
      ),
    ),
    title: $checkedConvert('title', (v) => v as String),
    locator: $checkedConvert(
      'locator',
      (v) => SchoolAssistanceSourceLocator.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceCitationToJson(
  SchoolAssistanceCitation instance,
) => <String, dynamic>{
  'token': instance.token,
  'kind': _$SchoolAssistanceCitationKindEnumEnumMap[instance.kind]!,
  'title': instance.title,
  'locator': instance.locator.toJson(),
};

const _$SchoolAssistanceCitationKindEnumEnumMap = {
  SchoolAssistanceCitationKindEnum.document: 'document',
  SchoolAssistanceCitationKindEnum.calendar: 'calendar',
  SchoolAssistanceCitationKindEnum.releasedMarks: 'released_marks',
  SchoolAssistanceCitationKindEnum.attendance: 'attendance',
  SchoolAssistanceCitationKindEnum.supportMessage: 'support_message',
  SchoolAssistanceCitationKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
