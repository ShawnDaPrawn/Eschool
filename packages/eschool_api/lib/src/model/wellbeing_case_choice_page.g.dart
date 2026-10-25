// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_choice_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseChoicePage _$WellbeingCaseChoicePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseChoicePage',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
    final val = WellbeingCaseChoicePage(
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WellbeingNamedChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      selectedAvailable: $checkedConvert(
        'selected_available',
        (v) => v as bool?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'lastPage': 'last_page',
    'selectedAvailable': 'selected_available',
  },
);

Map<String, dynamic> _$WellbeingCaseChoicePageToJson(
  WellbeingCaseChoicePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'selected_available': ?instance.selectedAvailable,
};
