// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_subject.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiarySubject _$DiarySubjectFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiarySubject', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = DiarySubject(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$DiarySubjectToJson(DiarySubject instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
