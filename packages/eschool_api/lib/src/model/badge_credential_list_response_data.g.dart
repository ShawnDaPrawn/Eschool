// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'badge_credential_list_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BadgeCredentialListResponseData _$BadgeCredentialListResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BadgeCredentialListResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['available', 'items']);
  final val = BadgeCredentialListResponseData(
    available: $checkedConvert('available', (v) => v as bool),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => BadgeCredentialListResponseDataItemsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$BadgeCredentialListResponseDataToJson(
  BadgeCredentialListResponseData instance,
) => <String, dynamic>{
  'available': instance.available,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
