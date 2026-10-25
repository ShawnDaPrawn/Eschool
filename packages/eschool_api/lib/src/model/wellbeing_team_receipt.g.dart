// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_team_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingTeamReceipt _$WellbeingTeamReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingTeamReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'category_id',
        'user_id',
        'active',
        'revision',
        'starts_on',
        'ends_on',
      ],
    );
    final val = WellbeingTeamReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'categoryId': 'category_id',
    'userId': 'user_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$WellbeingTeamReceiptToJson(
  WellbeingTeamReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'category_id': instance.categoryId,
  'user_id': instance.userId,
  'active': instance.active,
  'revision': instance.revision,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
