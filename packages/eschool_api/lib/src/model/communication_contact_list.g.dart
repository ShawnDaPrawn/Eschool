// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_contact_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationContactList _$CommunicationContactListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationContactList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'last_page', 'items']);
  final val = CommunicationContactList(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => CommunicationConfigurationYearsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$CommunicationContactListToJson(
  CommunicationContactList instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
