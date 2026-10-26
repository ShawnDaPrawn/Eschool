// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceConfiguration _$SchoolAssistanceConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'can_parent',
        'can_office',
        'languages',
        'answer_available',
        'translation_available',
        'school_id',
        'reader_id',
      ],
    );
    final val = SchoolAssistanceConfiguration(
      writable: $checkedConvert('writable', (v) => v as bool?),
      canParent: $checkedConvert('can_parent', (v) => v as bool),
      canOffice: $checkedConvert('can_office', (v) => v as bool),
      languages: $checkedConvert(
        'languages',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      answerAvailable: $checkedConvert('answer_available', (v) => v as bool),
      translationAvailable: $checkedConvert(
        'translation_available',
        (v) => v as bool,
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'canParent': 'can_parent',
    'canOffice': 'can_office',
    'answerAvailable': 'answer_available',
    'translationAvailable': 'translation_available',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceConfigurationToJson(
  SchoolAssistanceConfiguration instance,
) => <String, dynamic>{
  'writable': ?instance.writable,
  'can_parent': instance.canParent,
  'can_office': instance.canOffice,
  'languages': instance.languages,
  'answer_available': instance.answerAvailable,
  'translation_available': instance.translationAvailable,
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};
