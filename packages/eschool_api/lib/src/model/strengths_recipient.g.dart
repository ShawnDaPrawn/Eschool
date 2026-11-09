// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_recipient.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsRecipient _$StrengthsRecipientFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StrengthsRecipient', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['user_id', 'name', 'kind', 'detail'],
      );
      final val = StrengthsRecipient(
        userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$StrengthsRecipientKindEnumEnumMap,
            v,
            unknownValue: StrengthsRecipientKindEnum.unknownDefaultOpenApi,
          ),
        ),
        detail: $checkedConvert('detail', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'userId': 'user_id'});

Map<String, dynamic> _$StrengthsRecipientToJson(StrengthsRecipient instance) =>
    <String, dynamic>{
      'user_id': instance.userId,
      'name': instance.name,
      'kind': _$StrengthsRecipientKindEnumEnumMap[instance.kind]!,
      'detail': instance.detail,
    };

const _$StrengthsRecipientKindEnumEnumMap = {
  StrengthsRecipientKindEnum.guardian: 'guardian',
  StrengthsRecipientKindEnum.teacher: 'teacher',
  StrengthsRecipientKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
