// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_reading.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportReading _$LearnerPassportReadingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportReading', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['available', 'items']);
  final val = LearnerPassportReading(
    available: $checkedConvert('available', (v) => v as bool),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => LearnerPassportReadingItemsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerPassportReadingToJson(
  LearnerPassportReading instance,
) => <String, dynamic>{
  'available': instance.available,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
