// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_status.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyStatus _$EmergencyStatusFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'EmergencyStatus',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'alert',
            'children',
            'can_alert',
            'can_respond',
            'takes_roll_call',
          ],
        );
        final val = EmergencyStatus(
          alert: $checkedConvert(
            'alert',
            (v) => v == null
                ? null
                : EmergencyPublicAlert.fromJson(v as Map<String, dynamic>),
          ),
          children: $checkedConvert(
            'children',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      EmergencyChildStatus.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          canAlert: $checkedConvert('can_alert', (v) => v as bool),
          canRespond: $checkedConvert('can_respond', (v) => v as bool),
          takesRollCall: $checkedConvert('takes_roll_call', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'canAlert': 'can_alert',
        'canRespond': 'can_respond',
        'takesRollCall': 'takes_roll_call',
      },
    );

Map<String, dynamic> _$EmergencyStatusToJson(EmergencyStatus instance) =>
    <String, dynamic>{
      'alert': instance.alert?.toJson(),
      'children': instance.children.map((e) => e.toJson()).toList(),
      'can_alert': instance.canAlert,
      'can_respond': instance.canRespond,
      'takes_roll_call': instance.takesRollCall,
    };
