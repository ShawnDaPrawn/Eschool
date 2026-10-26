//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_left.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationLeft {
  /// Returns a new [FoundationLeft] instance.
  FoundationLeft({required this.left});

  @JsonKey(name: r'left', required: true, includeIfNull: false)
  final bool left;

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is FoundationLeft && other.left == left;

  @override
  int get hashCode => left.hashCode;

  factory FoundationLeft.fromJson(Map<String, dynamic> json) =>
      _$FoundationLeftFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationLeftToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
