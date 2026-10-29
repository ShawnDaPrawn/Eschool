//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_vendor_brief.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceVendorBrief {
  /// Returns a new [CommerceVendorBrief] instance.
  CommerceVendorBrief({
    required this.id,

    required this.name,

    required this.description,

    required this.kind,

    required this.currency,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceVendorBriefKindEnum.unknownDefaultOpenApi,
  )
  final CommerceVendorBriefKindEnum kind;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceVendorBrief &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.kind == kind &&
          other.currency == currency;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      kind.hashCode +
      currency.hashCode;

  factory CommerceVendorBrief.fromJson(Map<String, dynamic> json) =>
      _$CommerceVendorBriefFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceVendorBriefToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceVendorBriefKindEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'independent')
  independent(r'independent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommerceVendorBriefKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
