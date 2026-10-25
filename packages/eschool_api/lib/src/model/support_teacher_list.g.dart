// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_teacher_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportTeacherList _$SupportTeacherListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportTeacherList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['page', 'last_page', 'items']);
      final val = SupportTeacherList(
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => SupportTeacherDisclosure.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$SupportTeacherListToJson(SupportTeacherList instance) =>
    <String, dynamic>{
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
