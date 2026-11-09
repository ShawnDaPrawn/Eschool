// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractList _$ReenrolmentContractListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = ReenrolmentContractList(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ReenrolmentContractListItemsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentContractListToJson(
  ReenrolmentContractList instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
