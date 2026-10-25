// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_awards.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeAwards _$MyGradeAwardsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MyGradeAwards', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['available', 'items']);
      final val = MyGradeAwards(
        available: $checkedConvert('available', (v) => v as bool),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => MyGradeAwardChoice.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$MyGradeAwardsToJson(MyGradeAwards instance) =>
    <String, dynamic>{
      'available': instance.available,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
