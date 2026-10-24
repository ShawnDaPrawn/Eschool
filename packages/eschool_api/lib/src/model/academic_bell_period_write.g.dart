// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_bell_period_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicBellPeriodWrite _$AcademicBellPeriodWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicBellPeriodWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['name', 'starts_at', 'ends_at', 'is_break'],
    );
    final val = AcademicBellPeriodWrite(
      name: $checkedConvert('name', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      isBreak: $checkedConvert('is_break', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'isBreak': 'is_break',
  },
);

Map<String, dynamic> _$AcademicBellPeriodWriteToJson(
  AcademicBellPeriodWrite instance,
) => <String, dynamic>{
  'name': instance.name,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'is_break': instance.isBreak,
};
