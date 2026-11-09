// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_share.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsShare _$StrengthsShareFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsShare',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'recipient_user_id',
            'recipient_name',
            'kind',
            'sections',
            'shared_on',
            'expires_at',
            'revoked_at',
            'last_viewed_at',
            'status',
          ],
        );
        final val = StrengthsShare(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          recipientUserId: $checkedConvert(
            'recipient_user_id',
            (v) => (v as num).toInt(),
          ),
          recipientName: $checkedConvert('recipient_name', (v) => v as String),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$StrengthsShareKindEnumEnumMap,
              v,
              unknownValue: StrengthsShareKindEnum.unknownDefaultOpenApi,
            ),
          ),
          sections: $checkedConvert(
            'sections',
            (v) => (v as List<dynamic>)
                .map((e) => $enumDecode(_$StrengthsSectionKeyEnumMap, e))
                .toList(),
          ),
          sharedOn: $checkedConvert(
            'shared_on',
            (v) => DateTime.parse(v as String),
          ),
          expiresAt: $checkedConvert(
            'expires_at',
            (v) => DateTime.parse(v as String),
          ),
          revokedAt: $checkedConvert(
            'revoked_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          lastViewedAt: $checkedConvert(
            'last_viewed_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$StrengthsShareStatusEnumEnumMap,
              v,
              unknownValue: StrengthsShareStatusEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'recipientUserId': 'recipient_user_id',
        'recipientName': 'recipient_name',
        'sharedOn': 'shared_on',
        'expiresAt': 'expires_at',
        'revokedAt': 'revoked_at',
        'lastViewedAt': 'last_viewed_at',
      },
    );

Map<String, dynamic> _$StrengthsShareToJson(StrengthsShare instance) =>
    <String, dynamic>{
      'id': instance.id,
      'recipient_user_id': instance.recipientUserId,
      'recipient_name': instance.recipientName,
      'kind': _$StrengthsShareKindEnumEnumMap[instance.kind]!,
      'sections': instance.sections
          .map((e) => _$StrengthsSectionKeyEnumMap[e]!)
          .toList(),
      'shared_on': instance.sharedOn.toIso8601String(),
      'expires_at': instance.expiresAt.toIso8601String(),
      'revoked_at': instance.revokedAt?.toIso8601String(),
      'last_viewed_at': instance.lastViewedAt?.toIso8601String(),
      'status': _$StrengthsShareStatusEnumEnumMap[instance.status]!,
    };

const _$StrengthsShareKindEnumEnumMap = {
  StrengthsShareKindEnum.guardian: 'guardian',
  StrengthsShareKindEnum.teacher: 'teacher',
  StrengthsShareKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
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

const _$StrengthsShareStatusEnumEnumMap = {
  StrengthsShareStatusEnum.open: 'open',
  StrengthsShareStatusEnum.revoked: 'revoked',
  StrengthsShareStatusEnum.expired: 'expired',
  StrengthsShareStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
