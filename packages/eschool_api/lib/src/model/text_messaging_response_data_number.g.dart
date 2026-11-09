// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'text_messaging_response_data_number.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TextMessagingResponseDataNumber _$TextMessagingResponseDataNumberFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TextMessagingResponseDataNumber',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'on_record',
        'source',
        'ending',
        'verified',
        'code_sent',
        'code_expires_at',
        'next_code_at',
        'locked_until',
        'can_verify',
      ],
    );
    final val = TextMessagingResponseDataNumber(
      onRecord: $checkedConvert('on_record', (v) => v as bool),
      source_: $checkedConvert(
        'source',
        (v) => $enumDecodeNullable(
          _$TextMessagingResponseDataNumberSource_EnumEnumMap,
          v,
          unknownValue:
              TextMessagingResponseDataNumberSource_Enum.unknownDefaultOpenApi,
        ),
      ),
      ending: $checkedConvert('ending', (v) => v as String?),
      verified: $checkedConvert('verified', (v) => v as bool),
      codeSent: $checkedConvert('code_sent', (v) => v as bool),
      codeExpiresAt: $checkedConvert(
        'code_expires_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      nextCodeAt: $checkedConvert(
        'next_code_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      lockedUntil: $checkedConvert(
        'locked_until',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      canVerify: $checkedConvert('can_verify', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'onRecord': 'on_record',
    'source_': 'source',
    'codeSent': 'code_sent',
    'codeExpiresAt': 'code_expires_at',
    'nextCodeAt': 'next_code_at',
    'lockedUntil': 'locked_until',
    'canVerify': 'can_verify',
  },
);

Map<String, dynamic> _$TextMessagingResponseDataNumberToJson(
  TextMessagingResponseDataNumber instance,
) => <String, dynamic>{
  'on_record': instance.onRecord,
  'source':
      _$TextMessagingResponseDataNumberSource_EnumEnumMap[instance.source_],
  'ending': instance.ending,
  'verified': instance.verified,
  'code_sent': instance.codeSent,
  'code_expires_at': instance.codeExpiresAt?.toIso8601String(),
  'next_code_at': instance.nextCodeAt?.toIso8601String(),
  'locked_until': instance.lockedUntil?.toIso8601String(),
  'can_verify': instance.canVerify,
};

const _$TextMessagingResponseDataNumberSource_EnumEnumMap = {
  TextMessagingResponseDataNumberSource_Enum.parent: 'parent',
  TextMessagingResponseDataNumberSource_Enum.account: 'account',
  TextMessagingResponseDataNumberSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
