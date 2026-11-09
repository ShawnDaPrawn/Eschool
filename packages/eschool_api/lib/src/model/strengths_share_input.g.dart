// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_share_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsShareInput _$StrengthsShareInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsShareInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'recipient_user_id', 'sections'],
        );
        final val = StrengthsShareInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          recipientUserId: $checkedConvert(
            'recipient_user_id',
            (v) => (v as num).toInt(),
          ),
          sections: $checkedConvert(
            'sections',
            (v) => (v as List<dynamic>)
                .map((e) => $enumDecode(_$StrengthsSectionKeyEnumMap, e))
                .toSet(),
          ),
          days: $checkedConvert('days', (v) => (v as num?)?.toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'recipientUserId': 'recipient_user_id',
      },
    );

Map<String, dynamic> _$StrengthsShareInputToJson(
  StrengthsShareInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'recipient_user_id': instance.recipientUserId,
  'sections': instance.sections
      .map((e) => _$StrengthsSectionKeyEnumMap[e]!)
      .toList(),
  'days': ?instance.days,
};

const _$StrengthsSectionKeyEnumMap = {
  StrengthsSectionKey.growing: 'growing',
  StrengthsSectionKey.remembering: 'remembering',
  StrengthsSectionKey.explored: 'explored',
  StrengthsSectionKey.activities: 'activities',
  StrengthsSectionKey.reading: 'reading',
  StrengthsSectionKey.mightEnjoy: 'might_enjoy',
  StrengthsSectionKey.unknownDefaultOpenApi: 'unknown_default_open_api',
};
