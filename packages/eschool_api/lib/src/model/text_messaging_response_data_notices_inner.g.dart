// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'text_messaging_response_data_notices_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TextMessagingResponseDataNoticesInner
_$TextMessagingResponseDataNoticesInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TextMessagingResponseDataNoticesInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const [
          'type',
          'label',
          'offered',
          'sms',
          'whatsapp',
          'own_choice',
        ],
      );
      final val = TextMessagingResponseDataNoticesInner(
        type: $checkedConvert('type', (v) => v as String),
        label: $checkedConvert('label', (v) => v as String),
        offered: $checkedConvert(
          'offered',
          (v) => (v as List<dynamic>)
              .map(
                (e) => $enumDecode(
                  _$TextMessagingResponseDataNoticesInnerOfferedEnumEnumMap,
                  e,
                  unknownValue: TextMessagingResponseDataNoticesInnerOfferedEnum
                      .unknownDefaultOpenApi,
                ),
              )
              .toList(),
        ),
        sms: $checkedConvert('sms', (v) => v as bool),
        whatsapp: $checkedConvert('whatsapp', (v) => v as bool),
        ownChoice: $checkedConvert('own_choice', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'ownChoice': 'own_choice'});

Map<String, dynamic> _$TextMessagingResponseDataNoticesInnerToJson(
  TextMessagingResponseDataNoticesInner instance,
) => <String, dynamic>{
  'type': instance.type,
  'label': instance.label,
  'offered': instance.offered
      .map((e) => _$TextMessagingResponseDataNoticesInnerOfferedEnumEnumMap[e]!)
      .toList(),
  'sms': instance.sms,
  'whatsapp': instance.whatsapp,
  'own_choice': instance.ownChoice,
};

const _$TextMessagingResponseDataNoticesInnerOfferedEnumEnumMap = {
  TextMessagingResponseDataNoticesInnerOfferedEnum.sms: 'sms',
  TextMessagingResponseDataNoticesInnerOfferedEnum.whatsapp: 'whatsapp',
  TextMessagingResponseDataNoticesInnerOfferedEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
