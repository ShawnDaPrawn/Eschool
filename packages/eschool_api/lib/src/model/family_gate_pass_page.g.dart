// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_pass_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGatePassPage _$FamilyGatePassPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyGatePassPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'next_page']);
      final val = FamilyGatePassPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => FamilyGatePass.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextPage': 'next_page'});

Map<String, dynamic> _$FamilyGatePassPageToJson(FamilyGatePassPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_page': instance.nextPage,
    };
