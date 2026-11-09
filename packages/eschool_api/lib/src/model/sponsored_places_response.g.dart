// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_places_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlacesResponse _$SponsoredPlacesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SponsoredPlacesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SponsoredPlacesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SponsoredPlaces.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SponsoredPlacesResponseToJson(
  SponsoredPlacesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
