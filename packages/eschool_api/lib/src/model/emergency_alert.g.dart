// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_alert.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyAlert _$EmergencyAlertFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyAlert',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'audience',
        'drill',
        'status',
        'title',
        'message',
        'revision',
        'drill_id',
        'prepared_by',
        'prepared_at',
        'confirmed_by',
        'activated_at',
        'all_clear_by',
        'all_clear_at',
        'all_clear_message',
        'cancelled_at',
        'recipient_count',
        'confirmation_word',
        'confirm_by',
      ],
    );
    final val = EmergencyAlert(
      id: $checkedConvert('id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$EmergencyAlertKindEnumEnumMap,
          v,
          unknownValue: EmergencyAlertKindEnum.unknownDefaultOpenApi,
        ),
      ),
      audience: $checkedConvert(
        'audience',
        (v) => $enumDecode(
          _$EmergencyAlertAudienceEnumEnumMap,
          v,
          unknownValue: EmergencyAlertAudienceEnum.unknownDefaultOpenApi,
        ),
      ),
      drill: $checkedConvert('drill', (v) => v as bool),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$EmergencyAlertStatusEnumEnumMap,
          v,
          unknownValue: EmergencyAlertStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      message: $checkedConvert('message', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      drillId: $checkedConvert('drill_id', (v) => v as String?),
      preparedBy: $checkedConvert('prepared_by', (v) => (v as num).toInt()),
      preparedAt: $checkedConvert(
        'prepared_at',
        (v) => DateTime.parse(v as String),
      ),
      confirmedBy: $checkedConvert('confirmed_by', (v) => (v as num?)?.toInt()),
      activatedAt: $checkedConvert(
        'activated_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      allClearBy: $checkedConvert('all_clear_by', (v) => (v as num?)?.toInt()),
      allClearAt: $checkedConvert(
        'all_clear_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      allClearMessage: $checkedConvert(
        'all_clear_message',
        (v) => v as String?,
      ),
      cancelledAt: $checkedConvert(
        'cancelled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      recipientCount: $checkedConvert(
        'recipient_count',
        (v) => (v as num).toInt(),
      ),
      confirmationWord: $checkedConvert(
        'confirmation_word',
        (v) => v as String?,
      ),
      confirmBy: $checkedConvert(
        'confirm_by',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'drillId': 'drill_id',
    'preparedBy': 'prepared_by',
    'preparedAt': 'prepared_at',
    'confirmedBy': 'confirmed_by',
    'activatedAt': 'activated_at',
    'allClearBy': 'all_clear_by',
    'allClearAt': 'all_clear_at',
    'allClearMessage': 'all_clear_message',
    'cancelledAt': 'cancelled_at',
    'recipientCount': 'recipient_count',
    'confirmationWord': 'confirmation_word',
    'confirmBy': 'confirm_by',
  },
);

Map<String, dynamic> _$EmergencyAlertToJson(EmergencyAlert instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$EmergencyAlertKindEnumEnumMap[instance.kind]!,
      'audience': _$EmergencyAlertAudienceEnumEnumMap[instance.audience]!,
      'drill': instance.drill,
      'status': _$EmergencyAlertStatusEnumEnumMap[instance.status]!,
      'title': instance.title,
      'message': instance.message,
      'revision': instance.revision,
      'drill_id': instance.drillId,
      'prepared_by': instance.preparedBy,
      'prepared_at': instance.preparedAt.toIso8601String(),
      'confirmed_by': instance.confirmedBy,
      'activated_at': instance.activatedAt?.toIso8601String(),
      'all_clear_by': instance.allClearBy,
      'all_clear_at': instance.allClearAt?.toIso8601String(),
      'all_clear_message': instance.allClearMessage,
      'cancelled_at': instance.cancelledAt?.toIso8601String(),
      'recipient_count': instance.recipientCount,
      'confirmation_word': instance.confirmationWord,
      'confirm_by': instance.confirmBy?.toIso8601String(),
    };

const _$EmergencyAlertKindEnumEnumMap = {
  EmergencyAlertKindEnum.lockdown: 'lockdown',
  EmergencyAlertKindEnum.evacuation: 'evacuation',
  EmergencyAlertKindEnum.shelter: 'shelter',
  EmergencyAlertKindEnum.critical: 'critical',
  EmergencyAlertKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$EmergencyAlertAudienceEnumEnumMap = {
  EmergencyAlertAudienceEnum.everyone: 'everyone',
  EmergencyAlertAudienceEnum.staff: 'staff',
  EmergencyAlertAudienceEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$EmergencyAlertStatusEnumEnumMap = {
  EmergencyAlertStatusEnum.pending: 'pending',
  EmergencyAlertStatusEnum.active: 'active',
  EmergencyAlertStatusEnum.allClear: 'all_clear',
  EmergencyAlertStatusEnum.cancelled: 'cancelled',
  EmergencyAlertStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
