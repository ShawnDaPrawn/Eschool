//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sponsored_place_learners_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SponsoredPlaceLearnersInner {
  /// Returns a new [SponsoredPlaceLearnersInner] instance.
  SponsoredPlaceLearnersInner({
    required this.learnerId,

    required this.name,

    required this.addedOn,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'added_on', required: true, includeIfNull: true)
  final DateTime? addedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SponsoredPlaceLearnersInner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.addedOn == addedOn;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      (addedOn == null ? 0 : addedOn.hashCode);

  factory SponsoredPlaceLearnersInner.fromJson(Map<String, dynamic> json) =>
      _$SponsoredPlaceLearnersInnerFromJson(json);

  Map<String, dynamic> toJson() => _$SponsoredPlaceLearnersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
