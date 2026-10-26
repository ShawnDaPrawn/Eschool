//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_person.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPerson {
  /// Returns a new [SecureCardPerson] instance.
  SecureCardPerson({required this.id, required this.name, required this.type});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPerson &&
          other.id == id &&
          other.name == name &&
          other.type == type;

  @override
  int get hashCode => id.hashCode + name.hashCode + type.hashCode;

  factory SecureCardPerson.fromJson(Map<String, dynamic> json) =>
      _$SecureCardPersonFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardPersonToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
