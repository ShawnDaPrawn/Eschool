//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationGroupChoice {
  /// Returns a new [FoundationGroupChoice] instance.
  FoundationGroupChoice({
    required this.id,

    required this.name,

    required this.eligible,

    required this.revision,

    required this.canAuthorize,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'eligible', required: true, includeIfNull: false)
  final bool eligible;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'can_authorize', required: true, includeIfNull: false)
  final bool canAuthorize;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.eligible == eligible &&
          other.revision == revision &&
          other.canAuthorize == canAuthorize;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      eligible.hashCode +
      revision.hashCode +
      canAuthorize.hashCode;

  factory FoundationGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$FoundationGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
