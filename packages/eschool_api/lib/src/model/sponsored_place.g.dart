// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_place.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlace _$SponsoredPlaceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SponsoredPlace',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'reference',
            'sponsor_name',
            'places',
            'status',
            'effective_status',
            'current_period_end',
            'learners',
          ],
        );
        final val = SponsoredPlace(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          reference: $checkedConvert('reference', (v) => v as String),
          sponsorName: $checkedConvert('sponsor_name', (v) => v as String),
          places: $checkedConvert('places', (v) => (v as num).toInt()),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$SponsoredPlaceStatusEnumEnumMap,
              v,
              unknownValue: SponsoredPlaceStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          effectiveStatus: $checkedConvert(
            'effective_status',
            (v) => $enumDecode(
              _$SponsoredPlaceEffectiveStatusEnumEnumMap,
              v,
              unknownValue:
                  SponsoredPlaceEffectiveStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          currentPeriodEnd: $checkedConvert(
            'current_period_end',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          learners: $checkedConvert(
            'learners',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => SponsoredPlaceLearnersInner.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'sponsorName': 'sponsor_name',
        'effectiveStatus': 'effective_status',
        'currentPeriodEnd': 'current_period_end',
      },
    );

Map<String, dynamic> _$SponsoredPlaceToJson(SponsoredPlace instance) =>
    <String, dynamic>{
      'id': instance.id,
      'reference': instance.reference,
      'sponsor_name': instance.sponsorName,
      'places': instance.places,
      'status': _$SponsoredPlaceStatusEnumEnumMap[instance.status]!,
      'effective_status':
          _$SponsoredPlaceEffectiveStatusEnumEnumMap[instance.effectiveStatus]!,
      'current_period_end': instance.currentPeriodEnd?.toIso8601String(),
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };

const _$SponsoredPlaceStatusEnumEnumMap = {
  SponsoredPlaceStatusEnum.requested: 'requested',
  SponsoredPlaceStatusEnum.active: 'active',
  SponsoredPlaceStatusEnum.cancelled: 'cancelled',
  SponsoredPlaceStatusEnum.ended: 'ended',
  SponsoredPlaceStatusEnum.declined: 'declined',
  SponsoredPlaceStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$SponsoredPlaceEffectiveStatusEnumEnumMap = {
  SponsoredPlaceEffectiveStatusEnum.requested: 'requested',
  SponsoredPlaceEffectiveStatusEnum.active: 'active',
  SponsoredPlaceEffectiveStatusEnum.lapsed: 'lapsed',
  SponsoredPlaceEffectiveStatusEnum.cancelled: 'cancelled',
  SponsoredPlaceEffectiveStatusEnum.ended: 'ended',
  SponsoredPlaceEffectiveStatusEnum.declined: 'declined',
  SponsoredPlaceEffectiveStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
