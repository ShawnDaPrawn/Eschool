// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskConfiguration _$RiskConfigurationFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskConfiguration',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'session_year_id',
            'can_manage',
            'can_read_signals',
            'grade_ids',
            'groups',
            'settings',
            'years',
          ],
        );
        final val = RiskConfiguration(
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          canManage: $checkedConvert('can_manage', (v) => v as bool),
          canReadSignals: $checkedConvert('can_read_signals', (v) => v as bool),
          gradeIds: $checkedConvert(
            'grade_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          groups: $checkedConvert(
            'groups',
            (v) => (v as List<dynamic>)
                .map((e) => ProgressGroup.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          settings: $checkedConvert(
            'settings',
            (v) => RiskSettings.fromJson(v as Map<String, dynamic>),
          ),
          years: $checkedConvert(
            'years',
            (v) => (v as List<dynamic>)
                .map((e) => ProgressOption.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          today: $checkedConvert('today', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'canManage': 'can_manage',
        'canReadSignals': 'can_read_signals',
        'gradeIds': 'grade_ids',
      },
    );

Map<String, dynamic> _$RiskConfigurationToJson(RiskConfiguration instance) =>
    <String, dynamic>{
      'session_year_id': instance.sessionYearId,
      'can_manage': instance.canManage,
      'can_read_signals': instance.canReadSignals,
      'grade_ids': instance.gradeIds,
      'groups': instance.groups.map((e) => e.toJson()).toList(),
      'settings': instance.settings.toJson(),
      'years': instance.years.map((e) => e.toJson()).toList(),
      'today': ?instance.today,
    };
