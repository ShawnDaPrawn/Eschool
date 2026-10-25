// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_assignment_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAssignmentPage _$WellbeingAssignmentPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingAssignmentPage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['items', 'page', 'last_page', 'owner_id'],
  );
  final val = WellbeingAssignmentPage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => WellbeingAssignment.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page', 'ownerId': 'owner_id'});

Map<String, dynamic> _$WellbeingAssignmentPageToJson(
  WellbeingAssignmentPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'owner_id': instance.ownerId,
};
