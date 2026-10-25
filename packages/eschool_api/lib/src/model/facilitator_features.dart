//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_features.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorFeatures {
  /// Returns a new [FacilitatorFeatures] instance.
  FacilitatorFeatures({
    required this.groups,

    required this.messages,

    required this.counselling,
  });

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final bool groups;

  @JsonKey(name: r'messages', required: true, includeIfNull: false)
  final bool messages;

  @JsonKey(name: r'counselling', required: true, includeIfNull: false)
  final bool counselling;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorFeatures &&
          other.groups == groups &&
          other.messages == messages &&
          other.counselling == counselling;

  @override
  int get hashCode =>
      groups.hashCode + messages.hashCode + counselling.hashCode;

  factory FacilitatorFeatures.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorFeaturesFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorFeaturesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
