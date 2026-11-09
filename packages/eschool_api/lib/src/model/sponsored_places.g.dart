// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_places.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlaces _$SponsoredPlacesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SponsoredPlaces', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['school_id', 'reader_id', 'sponsorships'],
      );
      final val = SponsoredPlaces(
        schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
        readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
        sponsorships: $checkedConvert(
          'sponsorships',
          (v) => (v as List<dynamic>)
              .map((e) => SponsoredPlace.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$SponsoredPlacesToJson(SponsoredPlaces instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'sponsorships': instance.sponsorships.map((e) => e.toJson()).toList(),
    };
