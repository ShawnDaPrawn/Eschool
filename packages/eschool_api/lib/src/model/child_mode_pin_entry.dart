//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_pin_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModePinEntry {
  /// Returns a new [ChildModePinEntry] instance.
  ChildModePinEntry({required this.pin});

  @JsonKey(name: r'pin', required: true, includeIfNull: false)
  final String pin;

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is ChildModePinEntry && other.pin == pin;

  @override
  int get hashCode => pin.hashCode;

  factory ChildModePinEntry.fromJson(Map<String, dynamic> json) =>
      _$ChildModePinEntryFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModePinEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
