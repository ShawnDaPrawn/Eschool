// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_groups_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingGroupsResponseData _$TeachingGroupsResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingGroupsResponseData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['groups', 'current_page', 'last_page', 'total'],
  );
  final val = TeachingGroupsResponseData(
    groups: $checkedConvert(
      'groups',
      (v) => (v as List<dynamic>)
          .map((e) => TeachingGroup.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'lastPage': 'last_page'});

Map<String, dynamic> _$TeachingGroupsResponseDataToJson(
  TeachingGroupsResponseData instance,
) => <String, dynamic>{
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'last_page': instance.lastPage,
  'total': instance.total,
};
