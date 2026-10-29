// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_deleted.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryDeleted _$DiaryDeletedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryDeleted', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id']);
      final val = DiaryDeleted(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$DiaryDeletedToJson(DiaryDeleted instance) =>
    <String, dynamic>{'id': instance.id};
