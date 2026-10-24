// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_bell_period.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicBellPeriod _$AcademicBellPeriodFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicBellPeriod',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'name',
            'starts_at',
            'ends_at',
            'is_break',
            'id',
            'position',
          ],
        );
        final val = AcademicBellPeriod(
          name: $checkedConvert('name', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String),
          endsAt: $checkedConvert('ends_at', (v) => v as String),
          isBreak: $checkedConvert('is_break', (v) => v as bool),
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          position: $checkedConvert('position', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
        'isBreak': 'is_break',
      },
    );

Map<String, dynamic> _$AcademicBellPeriodToJson(AcademicBellPeriod instance) =>
    <String, dynamic>{
      'name': instance.name,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
      'is_break': instance.isBreak,
      'id': instance.id,
      'position': instance.position,
    };
