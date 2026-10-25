//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_house.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionHouse {
  /// Returns a new [RecognitionHouse] instance.
  RecognitionHouse({
    required this.id,

    required this.name,

    required this.emblem,

    required this.primaryColour,

    required this.secondaryColour,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'emblem', required: true, includeIfNull: false)
  final String emblem;

  @JsonKey(name: r'primary_colour', required: true, includeIfNull: false)
  final String primaryColour;

  @JsonKey(name: r'secondary_colour', required: true, includeIfNull: false)
  final String secondaryColour;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionHouse &&
          other.id == id &&
          other.name == name &&
          other.emblem == emblem &&
          other.primaryColour == primaryColour &&
          other.secondaryColour == secondaryColour &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      emblem.hashCode +
      primaryColour.hashCode +
      secondaryColour.hashCode +
      active.hashCode +
      revision.hashCode;

  factory RecognitionHouse.fromJson(Map<String, dynamic> json) =>
      _$RecognitionHouseFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionHouseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
