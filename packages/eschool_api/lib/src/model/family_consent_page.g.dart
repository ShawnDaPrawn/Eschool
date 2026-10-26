// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentPage _$FamilyConsentPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyConsentPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'next_page']);
      final val = FamilyConsentPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => FamilyConsentSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextPage': 'next_page'});

Map<String, dynamic> _$FamilyConsentPageToJson(FamilyConsentPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_page': instance.nextPage,
    };
