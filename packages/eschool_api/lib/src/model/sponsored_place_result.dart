//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sponsored_place.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sponsored_place_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SponsoredPlaceResult {
  /// Returns a new [SponsoredPlaceResult] instance.
  SponsoredPlaceResult({
    required this.schoolId,

    required this.readerId,

    required this.sponsorship,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'sponsorship', required: true, includeIfNull: false)
  final SponsoredPlace sponsorship;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SponsoredPlaceResult &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.sponsorship == sponsorship;

  @override
  int get hashCode =>
      schoolId.hashCode + readerId.hashCode + sponsorship.hashCode;

  factory SponsoredPlaceResult.fromJson(Map<String, dynamic> json) =>
      _$SponsoredPlaceResultFromJson(json);

  Map<String, dynamic> toJson() => _$SponsoredPlaceResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
