// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_audience_choice_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationAudienceChoicePage _$ConsultationAudienceChoicePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationAudienceChoicePage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'items',
        'page',
        'last_page',
        'selected_id',
        'selected_name',
      ],
    );
    final val = ConsultationAudienceChoicePage(
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => ConsultationChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      selectedId: $checkedConvert('selected_id', (v) => (v as num?)?.toInt()),
      selectedName: $checkedConvert('selected_name', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'lastPage': 'last_page',
    'selectedId': 'selected_id',
    'selectedName': 'selected_name',
  },
);

Map<String, dynamic> _$ConsultationAudienceChoicePageToJson(
  ConsultationAudienceChoicePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'selected_id': instance.selectedId,
  'selected_name': instance.selectedName,
};
