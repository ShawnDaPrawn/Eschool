// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportAccess _$SupportAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportAccess', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['audiences', 'can_write', 'today']);
      final val = SupportAccess(
        audiences: $checkedConvert(
          'audiences',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
        canWrite: $checkedConvert('can_write', (v) => v as bool),
        today: $checkedConvert('today', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'canWrite': 'can_write'});

Map<String, dynamic> _$SupportAccessToJson(SupportAccess instance) =>
    <String, dynamic>{
      'audiences': instance.audiences,
      'can_write': instance.canWrite,
      'today': instance.today,
    };
