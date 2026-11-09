//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sponsored_place.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sponsored_places.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SponsoredPlaces {
  /// Returns a new [SponsoredPlaces] instance.
  SponsoredPlaces({
    required this.schoolId,

    required this.readerId,

    required this.sponsorships,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'sponsorships', required: true, includeIfNull: false)
  final List<SponsoredPlace> sponsorships;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SponsoredPlaces &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.sponsorships == sponsorships;

  @override
  int get hashCode =>
      schoolId.hashCode + readerId.hashCode + sponsorships.hashCode;

  factory SponsoredPlaces.fromJson(Map<String, dynamic> json) =>
      _$SponsoredPlacesFromJson(json);

  Map<String, dynamic> toJson() => _$SponsoredPlacesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
