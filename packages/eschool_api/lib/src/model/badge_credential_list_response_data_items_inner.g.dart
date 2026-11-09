// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'badge_credential_list_response_data_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BadgeCredentialListResponseDataItemsInner
_$BadgeCredentialListResponseDataItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BadgeCredentialListResponseDataItemsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'presentation_id',
        'title',
        'presented_on',
        'kind',
        'issued',
      ],
    );
    final val = BadgeCredentialListResponseDataItemsInner(
      presentationId: $checkedConvert(
        'presentation_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      presentedOn: $checkedConvert(
        'presented_on',
        (v) => DateTime.parse(v as String),
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$BadgeCredentialListResponseDataItemsInnerKindEnumEnumMap,
          v,
          unknownValue: BadgeCredentialListResponseDataItemsInnerKindEnum
              .unknownDefaultOpenApi,
        ),
      ),
      issued: $checkedConvert('issued', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'presentationId': 'presentation_id',
    'presentedOn': 'presented_on',
  },
);

Map<String, dynamic> _$BadgeCredentialListResponseDataItemsInnerToJson(
  BadgeCredentialListResponseDataItemsInner instance,
) => <String, dynamic>{
  'presentation_id': instance.presentationId,
  'title': instance.title,
  'presented_on': instance.presentedOn.toIso8601String(),
  'kind':
      _$BadgeCredentialListResponseDataItemsInnerKindEnumEnumMap[instance
          .kind]!,
  'issued': instance.issued,
};

const _$BadgeCredentialListResponseDataItemsInnerKindEnumEnumMap = {
  BadgeCredentialListResponseDataItemsInnerKindEnum.award: 'award',
  BadgeCredentialListResponseDataItemsInnerKindEnum.badge: 'badge',
  BadgeCredentialListResponseDataItemsInnerKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
