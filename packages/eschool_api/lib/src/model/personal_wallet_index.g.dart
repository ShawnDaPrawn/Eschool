// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'personal_wallet_index.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PersonalWalletIndex _$PersonalWalletIndexFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PersonalWalletIndex',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['school_id', 'actor_user_id', 'currency', 'owners'],
    );
    final val = PersonalWalletIndex(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      owners: $checkedConvert(
        'owners',
        (v) => (v as List<dynamic>)
            .map((e) => WalletOwner.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'schoolId': 'school_id', 'actorUserId': 'actor_user_id'},
);

Map<String, dynamic> _$PersonalWalletIndexToJson(
  PersonalWalletIndex instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'currency': instance.currency,
  'owners': instance.owners.map((e) => e.toJson()).toList(),
};
