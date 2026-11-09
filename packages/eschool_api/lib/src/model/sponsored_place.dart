//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sponsored_place_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sponsored_place.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SponsoredPlace {
  /// Returns a new [SponsoredPlace] instance.
  SponsoredPlace({
    required this.id,

    required this.reference,

    required this.sponsorName,

    required this.places,

    required this.status,

    required this.effectiveStatus,

    required this.currentPeriodEnd,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'sponsor_name', required: true, includeIfNull: false)
  final String sponsorName;

  // minimum: 1
  @JsonKey(name: r'places', required: true, includeIfNull: false)
  final int places;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SponsoredPlaceStatusEnum.unknownDefaultOpenApi,
  )
  final SponsoredPlaceStatusEnum status;

  @JsonKey(
    name: r'effective_status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SponsoredPlaceEffectiveStatusEnum.unknownDefaultOpenApi,
  )
  final SponsoredPlaceEffectiveStatusEnum effectiveStatus;

  @JsonKey(name: r'current_period_end', required: true, includeIfNull: true)
  final DateTime? currentPeriodEnd;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<SponsoredPlaceLearnersInner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SponsoredPlace &&
          other.id == id &&
          other.reference == reference &&
          other.sponsorName == sponsorName &&
          other.places == places &&
          other.status == status &&
          other.effectiveStatus == effectiveStatus &&
          other.currentPeriodEnd == currentPeriodEnd &&
          other.learners == learners;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      sponsorName.hashCode +
      places.hashCode +
      status.hashCode +
      effectiveStatus.hashCode +
      (currentPeriodEnd == null ? 0 : currentPeriodEnd.hashCode) +
      learners.hashCode;

  factory SponsoredPlace.fromJson(Map<String, dynamic> json) =>
      _$SponsoredPlaceFromJson(json);

  Map<String, dynamic> toJson() => _$SponsoredPlaceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SponsoredPlaceStatusEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'ended')
  ended(r'ended'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SponsoredPlaceStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SponsoredPlaceEffectiveStatusEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'lapsed')
  lapsed(r'lapsed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'ended')
  ended(r'ended'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SponsoredPlaceEffectiveStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
