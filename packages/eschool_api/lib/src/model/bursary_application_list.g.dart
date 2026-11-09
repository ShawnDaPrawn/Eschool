// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_application_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryApplicationList _$BursaryApplicationListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryApplicationList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = BursaryApplicationList(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => BursaryApplicationListItemsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryApplicationListToJson(
  BursaryApplicationList instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
