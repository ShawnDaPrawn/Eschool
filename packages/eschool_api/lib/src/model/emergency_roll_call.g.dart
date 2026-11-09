// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCall _$EmergencyRollCallFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'EmergencyRollCall',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'alert_id',
            'drill',
            'status',
            'classes',
            'totals',
            'visitors_on_site',
          ],
        );
        final val = EmergencyRollCall(
          alertId: $checkedConvert('alert_id', (v) => v as String),
          drill: $checkedConvert('drill', (v) => v as bool),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$EmergencyRollCallStatusEnumEnumMap,
              v,
              unknownValue: EmergencyRollCallStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          classes: $checkedConvert(
            'classes',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => EmergencyRollCallClass.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
          totals: $checkedConvert(
            'totals',
            (v) => EmergencyRollCallTotals.fromJson(v as Map<String, dynamic>),
          ),
          visitorsOnSite: $checkedConvert(
            'visitors_on_site',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'alertId': 'alert_id',
        'visitorsOnSite': 'visitors_on_site',
      },
    );

Map<String, dynamic> _$EmergencyRollCallToJson(EmergencyRollCall instance) =>
    <String, dynamic>{
      'alert_id': instance.alertId,
      'drill': instance.drill,
      'status': _$EmergencyRollCallStatusEnumEnumMap[instance.status]!,
      'classes': instance.classes.map((e) => e.toJson()).toList(),
      'totals': instance.totals.toJson(),
      'visitors_on_site': instance.visitorsOnSite,
    };

const _$EmergencyRollCallStatusEnumEnumMap = {
  EmergencyRollCallStatusEnum.active: 'active',
  EmergencyRollCallStatusEnum.allClear: 'all_clear',
  EmergencyRollCallStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
