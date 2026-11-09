// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_place_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlaceResult _$SponsoredPlaceResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SponsoredPlaceResult', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['school_id', 'reader_id', 'sponsorship'],
  );
  final val = SponsoredPlaceResult(
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    sponsorship: $checkedConvert(
      'sponsorship',
      (v) => SponsoredPlace.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$SponsoredPlaceResultToJson(
  SponsoredPlaceResult instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'sponsorship': instance.sponsorship.toJson(),
};
