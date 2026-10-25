// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_disclosures.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportDisclosures _$SupportDisclosuresFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportDisclosures', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['plan_id', 'page', 'last_page', 'items'],
      );
      final val = SupportDisclosures(
        planId: $checkedConvert('plan_id', (v) => (v as num).toInt()),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => SupportDisclosureHistory.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'planId': 'plan_id', 'lastPage': 'last_page'});

Map<String, dynamic> _$SupportDisclosuresToJson(SupportDisclosures instance) =>
    <String, dynamic>{
      'plan_id': instance.planId,
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
