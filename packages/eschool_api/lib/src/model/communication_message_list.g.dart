// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageList _$CommunicationMessageListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'last_page', 'items']);
  final val = CommunicationMessageList(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => CommunicationSummary.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$CommunicationMessageListToJson(
  CommunicationMessageList instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
