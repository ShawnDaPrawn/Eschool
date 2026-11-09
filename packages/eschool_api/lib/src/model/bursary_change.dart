//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryChange {
  /// Returns a new [BursaryChange] instance.
  BursaryChange({
    required this.id,

    required this.state,

    required this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryChange &&
          other.id == id &&
          other.state == state &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + state.hashCode + revision.hashCode;

  factory BursaryChange.fromJson(Map<String, dynamic> json) =>
      _$BursaryChangeFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
