//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_pin_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModePinInput {
  /// Returns a new [ChildModePinInput] instance.
  ChildModePinInput({required this.pin, required this.pinConfirmation});

  @JsonKey(name: r'pin', required: true, includeIfNull: false)
  final String pin;

  @JsonKey(name: r'pin_confirmation', required: true, includeIfNull: false)
  final String pinConfirmation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModePinInput &&
          other.pin == pin &&
          other.pinConfirmation == pinConfirmation;

  @override
  int get hashCode => pin.hashCode + pinConfirmation.hashCode;

  factory ChildModePinInput.fromJson(Map<String, dynamic> json) =>
      _$ChildModePinInputFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModePinInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
