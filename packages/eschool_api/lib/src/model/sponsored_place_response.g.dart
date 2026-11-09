// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_place_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlaceResponse _$SponsoredPlaceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SponsoredPlaceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SponsoredPlaceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SponsoredPlaceResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SponsoredPlaceResponseToJson(
  SponsoredPlaceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
