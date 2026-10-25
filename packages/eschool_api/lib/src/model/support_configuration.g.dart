// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportConfiguration _$SupportConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SupportConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'audience',
        'page',
        'last_page',
        'learners',
        'staff',
        'staff_last_page',
      ],
    );
    final val = SupportConfiguration(
      audience: $checkedConvert(
        'audience',
        (v) => $enumDecode(
          _$SupportConfigurationAudienceEnumEnumMap,
          v,
          unknownValue: SupportConfigurationAudienceEnum.unknownDefaultOpenApi,
        ),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map((e) => SupportNamedChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      staff: $checkedConvert(
        'staff',
        (v) => (v as List<dynamic>)
            .map((e) => SupportNamedChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      staffLastPage: $checkedConvert(
        'staff_last_page',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'lastPage': 'last_page',
    'staffLastPage': 'staff_last_page',
  },
);

Map<String, dynamic> _$SupportConfigurationToJson(
  SupportConfiguration instance,
) => <String, dynamic>{
  'audience': _$SupportConfigurationAudienceEnumEnumMap[instance.audience]!,
  'page': instance.page,
  'last_page': instance.lastPage,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
  'staff': instance.staff.map((e) => e.toJson()).toList(),
  'staff_last_page': instance.staffLastPage,
};

const _$SupportConfigurationAudienceEnumEnumMap = {
  SupportConfigurationAudienceEnum.principal: 'principal',
  SupportConfigurationAudienceEnum.professional: 'professional',
  SupportConfigurationAudienceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
