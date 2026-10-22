// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_rule.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationRule _$NotificationRuleFromJson(Map<String, dynamic> json) =>
    $checkedCreate('NotificationRule', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['type', 'channel', 'mode']);
      final val = NotificationRule(
        type: $checkedConvert('type', (v) => v as String),
        channel: $checkedConvert('channel', (v) => v as String),
        mode: $checkedConvert('mode', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$NotificationRuleToJson(NotificationRule instance) =>
    <String, dynamic>{
      'type': instance.type,
      'channel': instance.channel,
      'mode': instance.mode,
    };
