// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contribute_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributeInput _$SharedLibraryContributeInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryContributeInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'practice_item_id',
        'library_topic_id',
        'title',
        'credit',
        'rights_confirmed',
        'licence_accepted',
      ],
    );
    final val = SharedLibraryContributeInput(
      practiceItemId: $checkedConvert(
        'practice_item_id',
        (v) => (v as num).toInt(),
      ),
      libraryTopicId: $checkedConvert(
        'library_topic_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      credit: $checkedConvert(
        'credit',
        (v) => $enumDecode(
          _$SharedLibraryContributeInputCreditEnumEnumMap,
          v,
          unknownValue:
              SharedLibraryContributeInputCreditEnum.unknownDefaultOpenApi,
        ),
      ),
      rightsConfirmed: $checkedConvert('rights_confirmed', (v) => v as bool),
      licenceAccepted: $checkedConvert('licence_accepted', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'practiceItemId': 'practice_item_id',
    'libraryTopicId': 'library_topic_id',
    'rightsConfirmed': 'rights_confirmed',
    'licenceAccepted': 'licence_accepted',
  },
);

Map<String, dynamic> _$SharedLibraryContributeInputToJson(
  SharedLibraryContributeInput instance,
) => <String, dynamic>{
  'practice_item_id': instance.practiceItemId,
  'library_topic_id': instance.libraryTopicId,
  'title': instance.title,
  'credit': _$SharedLibraryContributeInputCreditEnumEnumMap[instance.credit]!,
  'rights_confirmed': instance.rightsConfirmed,
  'licence_accepted': instance.licenceAccepted,
};

const _$SharedLibraryContributeInputCreditEnumEnumMap = {
  SharedLibraryContributeInputCreditEnum.named: 'named',
  SharedLibraryContributeInputCreditEnum.school: 'school',
  SharedLibraryContributeInputCreditEnum.anonymous: 'anonymous',
  SharedLibraryContributeInputCreditEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
