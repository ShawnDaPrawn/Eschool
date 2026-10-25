//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_replacement.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardReplacement {
  /// Returns a new [SecureCardReplacement] instance.
  SecureCardReplacement({required this.previous, required this.current});

  @JsonKey(name: r'previous', required: true, includeIfNull: false)
  final SecureCard previous;

  @JsonKey(name: r'current', required: true, includeIfNull: false)
  final SecureCard current;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardReplacement &&
          other.previous == previous &&
          other.current == current;

  @override
  int get hashCode => previous.hashCode + current.hashCode;

  factory SecureCardReplacement.fromJson(Map<String, dynamic> json) =>
      _$SecureCardReplacementFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardReplacementToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
