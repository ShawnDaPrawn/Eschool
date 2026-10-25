// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_health_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyHealthWrite _$FamilyHealthWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyHealthWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['values']);
      final val = FamilyHealthWrite(
        values: $checkedConvert(
          'values',
          (v) => (v as Map<String, dynamic>).map(
            (k, e) => MapEntry(
              k,
              FamilyHealthValue.fromJson(e as Map<String, dynamic>),
            ),
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$FamilyHealthWriteToJson(FamilyHealthWrite instance) =>
    <String, dynamic>{
      'values': instance.values.map((k, e) => MapEntry(k, e.toJson())),
    };
